<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Service\ClientProcedureTPModel;
use GCRM\CRMBundle\Service\ContractModel;
use GCRM\CRMBundle\Service\GTU;
use GCRM\CRMBundle\Service\TransactionProcedure;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wecoders\EnergyBundle\Entity\GTUInterface;
use Wecoders\EnergyBundle\Entity\InvoiceCollective;

class UpdateGtuOnBillingDocumentCommand extends Command
{
    private $em;
    private $contractModel;
    private $gtuModel;
    private $clientProcedureTPModel;

    public function __construct(EntityManager $em, ContractModel $contractModel, GTU $gtuModel, ClientProcedureTPModel $clientProcedureTPModel)
    {
        $this->em = $em;
        $this->contractModel = $contractModel;
        $this->gtuModel = $gtuModel;
        $this->clientProcedureTPModel = $clientProcedureTPModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('appbundle:update-gtu-on-billing-document-command')
            ->addArgument('entityName', InputArgument::REQUIRED, 'Give full entity name')
            ->addArgument('force', InputArgument::OPTIONAL, 'force update')
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $dateFrom = (new \DateTime())->setDate(2020, 10, 1)->setTime(0, 0);
        $qb = $this->em->createQueryBuilder();
        $invoices = $qb->select('a')
            ->from($input->getArgument('entityName'), 'a')
            ->where('a.createdDate >= :dateFrom')
            ->setParameters([
                'dateFrom' => $dateFrom
            ])
            ->getQuery()
            ->getResult()
        ;
        $tpProcedureMethod = 'setTransactionProcedure' . TransactionProcedure::TP;

        /** @var GTUInterface $invoice */
        $index = 1;
        foreach ($invoices as &$invoice) {
            if (!$input->getArgument('force') && !$this->canUpdate($invoice)) {
                dump('omited');
                continue;
            }

            if ($invoice instanceof InvoiceCollective) {
                $data = $invoice->getInvoicesData();
            } else {
                $data = $invoice->getData();
            }
            // todo: assign gtu codes
            foreach ($data as &$item) {
                foreach ($item['services'] as &$service) {
                    $service['gtu'] = GTU::GTU_02;
                }
            }

            if ($invoice instanceof InvoiceCollective) {
                $invoice->setInvoicesData($data);
                $data = $invoice->getInvoicesData();
            } else {
                $invoice->setData($data);
                $data = $invoice->getData($data);

                // set procedure codes
                $isTpProcedure = $this->clientProcedureTPModel->getRecordByClient($invoice->getClient());
                if ($isTpProcedure) {
                    $invoice->$tpProcedureMethod(true);
                }
            }

            $this->em->persist($invoice);
            $this->em->flush($invoice);
            $this->gtuModel->updateGTU($invoice, $data);

            dump($index);
            $index++;
        }

        dump('Success');
    }

    private function canUpdate($invoice)
    {
        $toCheck = [
            $invoice->getGtu1(),
            $invoice->getGtu2(),
            $invoice->getGtu3(),
            $invoice->getGtu4(),
            $invoice->getGtu5(),
            $invoice->getGtu6(),
            $invoice->getGtu7(),
            $invoice->getGtu8(),
            $invoice->getGtu9(),
            $invoice->getGtu10(),
            $invoice->getGtu11(),
            $invoice->getGtu12(),
            $invoice->getGtu13(),
            $invoice->getTransactionProcedure1(),
            $invoice->getTransactionProcedure2(),
            $invoice->getTransactionProcedure3(),
            $invoice->getTransactionProcedure4(),
            $invoice->getTransactionProcedure5(),
            $invoice->getTransactionProcedure6(),
            $invoice->getTransactionProcedure7(),
            $invoice->getTransactionProcedure8(),
            $invoice->getTransactionProcedure9(),
            $invoice->getTransactionProcedure10(),
            $invoice->getTransactionProcedure11(),
            $invoice->getTransactionProcedure12(),
            $invoice->getTransactionProcedure13()
        ];

        foreach ($toCheck as $item) {
            if ($item !== null) {
                return false;
            }
        }
        return true;
    }

}