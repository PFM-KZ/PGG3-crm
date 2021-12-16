<?php

namespace Wecoders\EnergyBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Service\ClientModel;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Entity\InvoiceProforma;

class UpdateClientsDataCommand extends Command
{
    /* @var EntityManager */
    private $em;

    private $container;
    private $clientModel;

    public function __construct(ContainerInterface $container, EntityManager $em, ClientModel $clientModel)
    {
        $this->em = $em;
        $this->container = $container;
        $this->clientModel = $clientModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('wecodersenergybundle:update-clients-data')
            ->setDescription('Update clients data.');
    }

    protected function getDataRows($file, $firstDataRowIndex, $highestColumn)
    {
        $reader = new Xlsx();
//        $reader = new Csv();
//        $reader->setInputEncoding('CP1250');
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->em;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $container = $this->container;

        $kernelRootDir = $container->get('kernel')->getRootDir();
        $rows = $this->getDataRows($kernelRootDir . '/../var/data/enrexenergy-clients-old-data.xlsx', 2, 'CK');

        $index = 1;
        foreach ($rows as $key => $row) {
            $name = $row[2];
            $surname = $row[3];
            $telephone = $row[4];
            $pesel = $row[5];
            $email = $row[8];

            $city = $row[9];
            $zipCode = $row[10];
            $street = $row[11];
            $houseNr = $row[12];
            $apartmentNr = $row[13];
            $postOffice = $row[14];
            $county = $row[15];

            $contactTelephone = $row[16];
            $correspondenceCity = $row[17];
            $correspondenceZipCode = $row[18];
            $correspondenceStreet = $row[19];
            $correspondenceHouseNr = $row[20];
            $correspondenceApartmentNr = $row[21];
            $correspondencePostOffice = $row[22];
            $correspondenceCounty = $row[23];

            $badgeId = $row[25];

            /** @var Client $client */
            $client = $this->clientModel->getClientByBadgeId($badgeId);
            if (!$client) {
                dump('Not found: ' . $badgeId);
                continue;
            }

            $client->setName($name);
            $client->setSurname($surname);
            $client->setTelephoneNr($telephone);
            $client->setPesel($pesel);
            $client->setEmail($email);

            $client->setCity($city);
            $client->setStreet($street);
            $client->setZipCode($zipCode);
            $client->setHouseNr($houseNr);
            $client->setApartmentNr($apartmentNr);
            $client->setPostOffice($postOffice);
            $client->setCounty($county);

            $client->setContactTelephoneNr($contactTelephone);
            $client->setCorrespondenceCity($correspondenceCity);
            $client->setCorrespondenceStreet($correspondenceStreet);
            $client->setCorrespondenceZipCode($correspondenceZipCode);
            $client->setCorrespondenceHouseNr($correspondenceHouseNr);
            $client->setCorrespondenceApartmentNr($correspondenceApartmentNr);
            $client->setCorrespondenceCounty($correspondenceCounty);
            $client->setCorrespondencePostOffice($correspondencePostOffice);

            $this->em->persist($client);
            $this->em->flush($client);

            dump($index);
            $index++;
        }


        dump('Success');
    }

}