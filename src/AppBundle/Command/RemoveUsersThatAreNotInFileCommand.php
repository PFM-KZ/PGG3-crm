<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Service\ContractModel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Service\SpreadsheetReader;

class RemoveUsersThatAreNotInFileCommand extends Command
{
    private $em;
    private $contractModel;
    private $container;
    private $spreadsheetReader;

    public function __construct(
        EntityManager $em,
        ContractModel $contractModel,
        ContainerInterface $container,
        SpreadsheetReader $spreadsheetReader
    )
    {
        $this->em = $em;
        $this->contractModel = $contractModel;
        $this->container = $container;
        $this->spreadsheetReader = $spreadsheetReader;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('appbundle:remove-users-that-are-not-in-file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $kernelRootDir = $this->container->get('kernel')->getRootDir();

        $fileActual = $kernelRootDir . '/../var/data/remove-users/test.xlsx';

        dump('Pobieranie kodów...');
        $rows = $this->spreadsheetReader->fetchRows('Xlsx', $fileActual, 1, 'A');

        $users = $this->em->getRepository(Client::class)->findAll();
        // hydrate
        $data = [];



        foreach ($users as $user)
        {
            $found = false;
            foreach ($rows as $row) {
                if ($user->getPesel() == trim($row[0]))
                {
                    dump($row[0]. " found");
                    $found = true;
                    break;
                }
            }

            if ($found == false)
            {
                $messages = $this->em->getRepository('WecodersEnergyBundle:SmsMessage')->findBy(['client' => $user]);

                foreach ($messages as $message)
                {
                    $this->em->remove($message);
                }
                $this->em->remove($user);
            }


        }

        $this->em->flush();


        //cliens check
        foreach ($rows as $row) {
            $user = $this->em->getRepository(Client::class)->findOneBy(['pesel' => $row[0]]);

            if (!$user)
            {
                dump("not found ".$row[0]." client");
            }
        }

        dump('Success');
    }

}