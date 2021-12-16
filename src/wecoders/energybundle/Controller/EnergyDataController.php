<?php


namespace Wecoders\EnergyBundle\Controller;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\ClientAndContractEnergy;
use GCRM\CRMBundle\Entity\ClientAndContractGas;
use GCRM\CRMBundle\Entity\ContractEnergy;
use GCRM\CRMBundle\Entity\ContractEnergyAndPpCode;
use GCRM\CRMBundle\Entity\ContractEnergyBase;
use GCRM\CRMBundle\Entity\ContractGas;
use GCRM\CRMBundle\Entity\ContractGasAndPpCode;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Wecoders\EnergyBundle\Entity\EnergyData;
use Wecoders\EnergyBundle\Service\ContractAccessor;
use Wecoders\EnergyBundle\Service\SpreadsheetReader;
use Wecoders\EnergyBundle\Service\Xlsx\XlsxReader;
use Wecoders\EnergyBundle\Service\Xlsx\XlsxWriter;

class EnergyDataController extends Controller
{
    private $em;
    private $xlsxReader;
    private $xlsxWriter;
    private $spreadsheetReader;

    function __construct(EntityManagerInterface $em, SpreadsheetReader $spreadsheetReader)
    {
        $this->em = $em;
        $this->xlsxReader = new XlsxReader();
        $this->xlsxWriter = new XlsxWriter();
        $this->spreadsheetReader = $spreadsheetReader;
    }

    /**
     * @Route("/energy-data/last-billing-period-list", name="admin_energy_data_last_billing_period_list")
     */
    public function lastBillingPeriodList(Request $request)
    {
        /** @var FormInterface $form */
        $form = $this->createFileForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form->getData()['file'];
            $rows = $this->xlsxReader->read($file->getPathname());
            $ppCodes = [];
            foreach($rows as $row) {
                $ppCodes[] = $row[0];
            }

            $result = $this->getContractsThatContainPpCodes($ppCodes);

            // get pp codes from fetched contracts
            /** @var ContractEnergyBase $contract */
            $ppCodes = [];
            $resultFromAllContracts = [];
            foreach ($result as $contract) {
                $contractAndPpCodes = $contract->getContractAndPpCodes();

                foreach ($contractAndPpCodes as $contractAndPpCode) {
                    $ppCodes[] = $contractAndPpCode->getPpCode();
                }

                // fetch last billing period to -> by last row of energy data for each pp and get only last record
                $qb = $this->em->getRepository(EnergyData::class)->createQueryBuilder('ed');
                $queryResult = $qb->select('ed.ppCode AS pp_code, MAX(ed.billingPeriodTo) AS max_billing_period_to')
                    ->where('ed.ppCode IN(:codes)')
                    ->setParameter('codes', $ppCodes)
                    ->groupBy('ed.ppCode')
                    ->orderBy('max_billing_period_to', 'DESC')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getResult()
                ;

                if ($queryResult && count($queryResult)) {
                    $resultFromAllContracts = array_merge($resultFromAllContracts, $queryResult);
                }
            }

            $result = array_map(function ($row) {
                $row['max_billing_period_to'] = (new \DateTime($row['max_billing_period_to']))->format('d.m.Y');
                return $row;
            }, $resultFromAllContracts);


            $writer = new Xlsx($this->xlsxWriter->write($result, ['Kod PP', 'Data ostatniego rozliczenia']));
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="export.xlsx"');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
            exit;
        }

        return $this->render('@WecodersEnergyBundle/default/file-form.html.twig', [
            'title' => 'Ostatni odczyt dla podanego kodu PP',
            'description' => 'Wzór formatki: <br> Kod pp',
            'form' => $form->createView(),
        ]);
    }

    private function getContractsThatContainPpCodes($ppCodes)
    {
        $dataProvider = [
            [
                ContractGas::class,
                ClientAndContractGas::class,
                ContractGasAndPpCode::class
            ],
            [
                ContractEnergy::class,
                ClientAndContractEnergy::class,
                ContractEnergyAndPpCode::class
            ],
        ];

        $result = [];
        foreach ($dataProvider as $data) {
            $qb = $this->em->createQueryBuilder();
            $queryResult = $qb->select('contract')
                ->from($data[0], 'contract')
                ->leftJoin($data[1], 'cac', 'WITH', 'cac.contract = contract.id')
                ->leftJoin(Client::class, 'client', 'WITH', 'cac.client = client.id')
                ->leftJoin($data[2], 'cappcode', 'WITH', 'cappcode.contract = contract.id')
                ->where('cappcode.ppCode IN(:codes)')
                ->setParameter('codes', $ppCodes)
                ->getQuery()
                ->getResult()
            ;
            if ($queryResult && count($queryResult)) {
                $result = array_merge($result, $queryResult);
            }
        }

        return $result;
    }

    /**
     * @Route("/energy-data/last-billing-period-list-with-limitation", name="admin_energy_data_last_billing_period_list_with_limitation")
     */
    public function billingPeriodFromWithDateLimitation(Request $request)
    {
        /** @var FormInterface $form */
        $form = $this->createFileForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form->getData()['file'];
            $rows = $this->spreadsheetReader->fetchRows('Xlsx', $file->getPathname(), 1, 'C');
            $data = [];
            foreach($rows as $row) {
                $ppCode = $row[0];
                $dateFrom = $row[1];
                $period = $row[2];

                $result = $this->getContractsThatContainPpCodes([$ppCode]);

                $ppCodes = [];
                foreach ($result as $contract) {
                    $contractAndPpCodes = $contract->getContractAndPpCodes();

                    foreach ($contractAndPpCodes as $contractAndPpCode) {
                        $ppCodes[] = $contractAndPpCode->getPpCode();
                    }

                    /** @var QueryBuilder $qb */
                    $qb = $this->em->getRepository(EnergyData::class)->createQueryBuilder('ed');
                    $queryResult = $qb->select('ed.ppCode AS pp_code, MAX(ed.billingPeriodFrom) as billing_period_from')
                        ->where('ed.ppCode IN(:codes)')
                        ->andWhere('ed.billingPeriodFrom >= :start AND ed.billingPeriodFrom <= :end')
                        ->groupBy('ed.ppCode')
                        ->orderBy('billing_period_from', 'DESC')
                        ->setMaxResults(1)
                        ->setParameters([
                            'start' => \DateTime::createFromFormat('m/d/Y', $dateFrom),
                            'end' => (\DateTime::createFromFormat('m/d/Y', $dateFrom))->modify('+' . $period . 'months'),
                            'codes' => $ppCodes,
                        ])
                        ->getQuery()
                        ->getOneOrNullResult()
                    ;

                    if ($queryResult) {
                        $data[] = $queryResult;
                    }
                }
            }

            $writer = new Xlsx($this->xlsxWriter->write($data, ['Kod PP', 'Data ostatniego rozliczenia']));
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="export.xlsx"');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
            exit;
        }

        return $this->render('@WecodersEnergyBundle/default/file-form.html.twig', [
            'title' => 'Ostatni odczyt dla podanego kodu PP z podanym okresem',
            'description' => 'Wzór formatki: <br> Kod pp | Data od | Ilość miesięcy',
            'form' => $form->createView(),
        ]);
    }

    private function createFileForm()
    {
        return $this->createFormBuilder()
            ->add('file', FileType::class, [
                'label' => 'label.file',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'label.send'
            ])
            ->getForm()
        ;
    }
}