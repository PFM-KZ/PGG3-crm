<?php

namespace Wecoders\EnergyBundle\Service;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\ContractEnergyBase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Entity\PackageToGenerate;

class ColonnadeModel
{
    private $em;
    private $container;

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function generateFromPackage(PackageToGenerate $package)
    {
        $em = $this->em;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);
        $container = $this->container;

        $kernelRootDir = $container->get('kernel')->getRootDir();

        $filePath = $kernelRootDir . '/../var/data/colonnade/Colonnade.xls';
        if (!file_exists($filePath)) {
            die('Brak szablonu.');
        }


        if ($package->getContractType() == 'ENERGY') {
            $clientAndContractEntity = 'GCRMCRMBundle:ClientAndContractEnergy';
            $contractEntity = 'GCRMCRMBundle:ContractEnergy';
        } elseif ($package->getContractType() == 'GAS') {
            $clientAndContractEntity = 'GCRMCRMBundle:ClientAndContractGas';
            $contractEntity = 'GCRMCRMBundle:ContractGas';
        } else {
            die('Zły typ umów paczki');
        }



        $contracts = $this->em->getRepository($contractEntity)->findBy(['id' => $package->getContractIds()]);
        if (!$contracts) {
            die('Nie można pobrać umów z paczki');
        }
        $clientAndContracts = $this->em->getRepository($clientAndContractEntity)->findBy(['contract' => $contracts]);
        if (!$clientAndContracts) {
            die('Nie można przypisać klientów do umów z paczki');
        }


        // adds data
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);

        $row = 2;
        foreach ($clientAndContracts as $clientAndContract) {
            /** @var Client $client */
            $client = $clientAndContract->getClient();
            /** @var ContractEnergyBase $contract */
            $contract = $clientAndContract->getContract();
            if (!$client) {
                die('Umowa nie jest przypisana do klienta: ' . $contract->getContractNumber());
            }

            if ($contract->invoicedAtLeastOnce()) {
                continue;
            }

            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setCellValueByColumnAndRow(2, $row, $client->getName());
            $sheet->setCellValueByColumnAndRow(3, $row, $client->getSurname());
            $sheet->setCellValueByColumnAndRow(4, $row, $client->getPesel());

            $dateOfBirth = $client->getDateOfBirthFromPesel();
            $sheet->setCellValueByColumnAndRow(5, $row, $dateOfBirth ? $dateOfBirth->format('Y-m-d') : null);

            $streetWithNumbers = null;
            if ($client->getHouseNr() && $client->getApartmentNr()) {
                $streetWithNumbers = $client->getStreet() . ' ' . $client->getHouseNr() . '/' . $client->getApartmentNr();
            } elseif ($client->getHouseNr()) {
                $streetWithNumbers = $client->getStreet() . ' ' . $client->getHouseNr();
            }
            $sheet->setCellValueByColumnAndRow(8, $row, $streetWithNumbers);
            $sheet->setCellValueByColumnAndRow(11, $row, $client->getCity());
            $sheet->setCellValueByColumnAndRow(12, $row, $client->getZipCode());

            $installationAddressPp = null;
            if ($contract->getPpHouseNr() && $contract->getPpApartmentNr()) {
                $installationAddressPp = $contract->getPpStreet() . ' ' . $contract->getPpHouseNr() . '/' . $contract->getPpApartmentNr();
            } elseif ($client->getHouseNr()) {
                $installationAddressPp = $contract->getPpStreet() . ' ' . $contract->getPpHouseNr();
            }

            $sheet->setCellValueByColumnAndRow(18, $row, $installationAddressPp);
            $sheet->setCellValueByColumnAndRow(21, $row, $contract->getPpCity());
            $sheet->setCellValueByColumnAndRow(22, $row, $contract->getPpZipCode());
            $sheet->setCellValueByColumnAndRow(26, $row, $client->getTelephoneNr());
            $sheet->setCellValueByColumnAndRow(27, $row, $client->getEmail());
            $sheet->setCellValueByColumnAndRow(107, $row, 'Assistance Elektryk');
            $sheet->setCellValueByColumnAndRow(108, $row, 'I');
            $sheet->setCellValueByColumnAndRow(109, $row, 'A');
            $sheet->setCellValueByColumnAndRow(110, $row, '1');
            $sheet->setCellValueByColumnAndRow(111, $row, 'K');

            $contractFromDate = $contract->getContractFromDate();
            $sheet->setCellValueByColumnAndRow(114, $row, $contractFromDate ? $contractFromDate->format('Y-m-d') : null);
            if ($contractFromDate && $contract->getPeriodInMonths()) {
                $periodInMonths = $contract->getPeriodInMonths();
                $contractToDate = clone $contractFromDate;
                $contractToDate->modify('+' . $periodInMonths . ' months');
                $contractToDate->modify('-1 day');
                $sheet->setCellValueByColumnAndRow(117, $row, $contractToDate->format('Y-m-d'));
            }

            $sheet->setCellValueByColumnAndRow(122, $row, 'AGREE');
            $sheet->setCellValueByColumnAndRow(129, $row, $contract->getContractNumber());

            $sheet->setCellValueByColumnAndRow(137, $row, 'ENREX');
            $sheet->setCellValueByColumnAndRow(138, $row, '0');
            $sheet->setCellValueByColumnAndRow(139, $row, '0');

            $sheet->setCellValueByColumnAndRow(154, $row, 'NO');
            $sheet->setCellValueByColumnAndRow(155, $row, $contract->getIsMarketingAgreementColonnade() ? 'YES' : 'NO');
            $sheet->setCellValueByColumnAndRow(156, $row, 'NO');

            $sheet->setCellValueByColumnAndRow(157, $row, $client->getAccountNumberIdentifier());

            $row++;
        }

        $this->downloadSpreadsheetAsXls($spreadsheet);
    }

    private function downloadSpreadsheetAsXls($spreadsheet)
    {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Colonnade.xls"');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($spreadsheet, 'Xls');
        $writer->save('php://output');
        exit;
    }

}