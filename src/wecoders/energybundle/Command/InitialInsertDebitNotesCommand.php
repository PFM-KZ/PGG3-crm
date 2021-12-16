<?php

namespace Wecoders\EnergyBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Service\BillingDocument\Initializer;
use GCRM\CRMBundle\Service\ClientModel;
use GCRM\CRMBundle\Service\ContractModel;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Entity\DebitNote;

class InitialInsertDebitNotesCommand extends Command
{
    /* @var EntityManager */
    private $em;

    private $container;

    private $contractModel;

    private $clientModel;

    private $initializer;

    public function __construct(EntityManager $em, ContainerInterface $container, ContractModel $contractModel, ClientModel $clientModel, Initializer $initializer)
    {
        $this->em = $em;
        $this->container = $container;
        $this->contractModel = $contractModel;
        $this->clientModel = $clientModel;
        $this->initializer = $initializer;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('wecodersenergybundle:initial-insert-debit-notes-command')
            ->addArgument('filename', InputArgument::REQUIRED, 'Filename with extension without path. The path is defined: /../var/data/')
            ->setDescription('Insert debit notes.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        /** @var Xlsx $reader */
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
        $rows = $this->getDataRowsUniversal($reader, $this->container->get('kernel')->getRootDir() . '/../var/data/' . $input->getArgument('filename'), 2, 'Q');
        if (!$rows) {
            dump('no data or bad filename specified');
            die;
        }

        foreach ($rows as $row) {
            $client = $this->clientModel->getClientByBadgeId($row[1]);
            if (!$client) {
                dump('not found client');
                die;
            }
        }

        $template = $this->em->getRepository('WecodersInvoiceBundle:InvoiceTemplate')->findOneBy(['code' => 'debit_note']);
        if (!$template) {
            dump('not found template');
            die;
        }

        $index = 1;
        foreach ($rows as $row) {
            $client = $this->clientModel->getClientByBadgeId($row[1]);
            $template = $this->em->getRepository('WecodersInvoiceBundle:InvoiceTemplate')->findOneBy(['code' => 'debit_note']);

            $debitNote = new DebitNote();
            $debitNote->setDocumentTemplate($template);
            $debitNote->setClient($client);

            $createdDate = \DateTime::createFromFormat('d.m.Y', $row[2]);
            $dateOfPayment = clone $createdDate;
            $dateOfPayment->modify('+14 days');
            $debitNote->setCreatedDate($createdDate);
            $debitNote->setDateOfPayment($dateOfPayment);

            $debitNote->setContent(trim($row[10]));
            $debitNote->setContractNumber($row[7]);


            $fullNamePieces = explode(' ', trim($row[3]));
            $name = '';
            $surname = '';
            if (isset($fullNamePieces[0])) {
                $name .= $fullNamePieces[0];
            }
            if (isset($fullNamePieces[1])) {
                $surname .= $fullNamePieces[1];
            }
            if (isset($fullNamePieces[2])) {
                $surname .= ' ' . $fullNamePieces[2];
            }
            if (isset($fullNamePieces[3])) {
                $surname .= ' ' . $fullNamePieces[3];
            }
            if (isset($fullNamePieces[4])) {
                $surname .= ' ' . $fullNamePieces[4];
            }
            if (isset($fullNamePieces[5])) {
                $surname .= ' ' . $fullNamePieces[5];
            }
            if (isset($fullNamePieces[6])) {
                $surname .= ' ' . $fullNamePieces[6];
            }

            $debitNote->setClientName($name);
            $debitNote->setClientSurname($surname);

            $debitNote->setSummaryGrossValue($row[12]);
            $debitNote->setBadgeId($row[1]);

            $debitNote->setClientZipCode($row[5]);
            $debitNote->setClientCity($row[6]);
            $debitNote->setClientAccountNumber($row[9]);
            $debitNote->setClientStreet($row[14]);
            $debitNote->setClientHouseNr($row[15]);
            $debitNote->setClientApartmentNr($row[16]);

            $this->em->persist($debitNote);
            $this->em->flush($debitNote);

            // update client invoices paid state
            $billingDocumentsObject = $this->initializer->init($client)->generate();
            $billingDocumentsObject->updateDocumentsIsPaidState();

            dump($index);
            $index++;
        }

        dump('Success');
    }

    protected function getDataRowsUniversal($reader, $file, $firstDataRowIndex, $highestColumn)
    {
        $spreadsheet = $reader->load($file);
        $worksheet = $spreadsheet->getActiveSheet();

        $highestRow = $worksheet->getHighestRow(); // e.g. 10
        $highestColumn++;

        $rows = [];

        for ($row = $firstDataRowIndex; $row <= $highestRow; ++$row) {
            $rows[$row] = [];
            for ($col = 'A'; $col != $highestColumn; ++$col) {
                $rows[$row][] = $worksheet->getCell($col . $row)->getFormattedValue();
            }
        }

        return $rows;
    }

}