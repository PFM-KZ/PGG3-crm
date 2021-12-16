<?php

namespace Wecoders\EnergyBundle\Controller;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Service\PaymentModel;
use GCRM\CRMBundle\Service\ValidateRoleAccess;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Wecoders\EnergyBundle\Form\Statistics\ActiveClientsType;
use Wecoders\EnergyBundle\Form\Statistics\ConfigType;
use Wecoders\EnergyBundle\Service\StatisticsModel;

class AdminStatisticsController extends Controller
{
    /**
     * @Route("/management", name="management")
     */
    public function getManagementAction(Request $request, EntityManager $em, StatisticsModel $statisticsModel)
    {
        $now = new \DateTime();
        $firstDayOfThisMonth = (clone $now)->setTime(0, 0)->modify('first day of this month');
        $lastDayOfThisMonth = (clone $now)->setTime(0, 0)->modify('last day of this month');
        $firstDayOfLastMonth = (clone $now)->setTime(0, 0)->modify('first day of last month');
        $lastDayOfLastMonth = (clone $now)->setTime(0, 0)->modify('last day of last month');
        $firstDayOfThisYear = (new \DateTime())->setTime(0, 0)->setDate($now->format('Y'), 1, 1);

        $activePp = $statisticsModel->getActiveClients();

        $paymentsCurrentMonth = $statisticsModel->getPayments($firstDayOfThisMonth, $lastDayOfThisMonth, ConfigType::GROUP_BY_TYPE_MONTH);
        $paymentsBeforeMonth = $statisticsModel->getPayments($firstDayOfLastMonth, $lastDayOfLastMonth, ConfigType::GROUP_BY_TYPE_MONTH);
        $payments = $statisticsModel->getPayments($firstDayOfThisYear, null, ConfigType::GROUP_BY_TYPE_MONTH);
//        $maxPaymentsValue = max($paymentsCurrentMonth['summary'], $paymentsBeforeMonth['summary']);
//        $minPaymentsValue = min($paymentsCurrentMonth['summary'], $paymentsBeforeMonth['summary']);
        if ($paymentsCurrentMonth['summary'] == $paymentsBeforeMonth['summary']) {
            $paymentsDiffPercentage = 0;
        } elseif ($paymentsCurrentMonth['summary'] < $paymentsBeforeMonth['summary']) {
            // price goes down
            $a = $paymentsBeforeMonth['summary'];
            $b = $paymentsCurrentMonth['summary'];
            $paymentsDiffPercentage = -(($a - $b) / $a * 100);
        } else {
            // price goes up
            $a = $paymentsBeforeMonth['summary'];
            $b = $paymentsCurrentMonth['summary'];
            $paymentsDiffPercentage = ($b - $a) / $a * 100;
        }

        $plannedRevenuesAll = $statisticsModel->getPlannedRevenues(null, null);

        $plannedRevenues = $statisticsModel->getPlannedRevenues($firstDayOfThisMonth, $lastDayOfThisMonth);
        $plannedRevenuesBeforeMonth = $statisticsModel->getPlannedRevenues($firstDayOfLastMonth, $lastDayOfLastMonth);
//        $maxPlannedRevenueValue = max($plannedRevenues['data'][0]['count'], $plannedRevenuesBeforeMonth['data'][0]['count']);
//        $minPlannedRevenueValue = min($plannedRevenues['data'][0]['count'], $plannedRevenuesBeforeMonth['data'][0]['count']);

        if ($plannedRevenues['data'][0]['count'] == $plannedRevenuesBeforeMonth['data'][0]['count']) {
            $plannedRevenuesDiffPercentage = 0;
        } elseif ($plannedRevenues['data'][0]['count'] < $plannedRevenuesBeforeMonth['data'][0]['count']) {
            // price goes down
            $a = $plannedRevenuesBeforeMonth['data'][0]['count'];
            $b = $plannedRevenues['data'][0]['count'];
            $plannedRevenuesDiffPercentage = -(($a - $b) / $a * 100);
        } else {
            // price goes up
            $a = $plannedRevenuesBeforeMonth['data'][0]['count'];
            $b = $plannedRevenues['data'][0]['count'];
            $plannedRevenuesDiffPercentage = ($b - $a) / $a * 100;
        }


//        if ($plannedRevenues['data'][0]['count'] < $plannedRevenuesBeforeMonth['data'][0]['count']) {
//            $plannedRevenuesDiffPercentage = -$plannedRevenuesDiffPercentage;
//        }

        return $this->render('@WecodersEnergyBundle/default/management-layout.html.twig', [
            'activePp' => $activePp,

            'payments' => $payments['summary'],
            'paymentsCurrentMonth' => $paymentsCurrentMonth['summary'],
            'paymentsBeforeMonth' => $paymentsBeforeMonth['summary'],
            'paymentsDiffPercentage' => $paymentsDiffPercentage,

            'plannedRevenues' => $plannedRevenues['data'][0]['count'],
            'plannedRevenuesBeforeMonth' => $plannedRevenuesBeforeMonth['data'][0]['count'],
            'plannedRevenuesDiffPercentage' => $plannedRevenuesDiffPercentage,
            'plannedRevenuesSummary' => $plannedRevenuesAll['summary'],
        ]);
    }

    /**
     * @Route("/management-active-pp", name="managementActivePp")
     */
    public function getManagementActivePpAction(Request $request, RequestStack $requestStack, EntityManager $em, StatisticsModel $statisticsModel)
    {
        $chartTemplate = '@WecodersEnergyBundle/charts/simple-column-chart.html.twig';

        $dbData = $statisticsModel->getActiveClients();
        $form = $this->createForm(ActiveClientsType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $headers = [
                'Lp.',
                'ID',
//                'Imię',
//                'Nazwisko',
                'Miasto',
                'Unikalny numer rachunku',
                'Typ umowy',
//                'Aktualny status',
//                'Marka',
                'Taryfa',
                'Zużycie (kWh)',
//                'Cennik (aktualny)'
            ];
            $data = [];
            $lp = 1;
            $mergedData = array_merge($dbData['raw']['ENERGY'], $dbData['raw']['GAS']);
            foreach ($mergedData as $row) {
                $type = null;
                if ($row['type'] == 'ENERGY') {
                    $type = 'Prąd';
                } elseif ($row['type'] == 'GAS') {
                    $type = 'Gaz';
                }
                $data[] = [
                    $lp++,
                    $row['id'],
//                    $row['name'],
//                    $row['surname'],
                    $row['city'],
                    $row['badge_id'],
                    $type,
//                    '',
//                    $row['brand_title'],
                    $row['tariff_title'],
                    $row['consumption'],
//                    ''
                ];
            }

            $this->downloadSpreadsheetAsXlsx($this->createSpreadsheet($headers, $data));
        }

        $data = [
            'dbData' => $dbData
        ];

        return $this->render('@WecodersEnergyBundle/default/management.html.twig', [
            'title' => 'Aktywne punkty poboru',
            'dbData' => $dbData,
            'form' => $form->createView(),
            'chartTemplate' => $chartTemplate,
        ]);
    }

    /**
     * @Route("/management-payments", name="managementPayments")
     */
    public function getManagementPaymentsAction(Request $request, RequestStack $requestStack, EntityManager $em, StatisticsModel $statisticsModel)
    {
        $chartTemplate = '@WecodersEnergyBundle/charts/line-graph.html.twig';

        $lsDateFrom = $request->query->has('lsDateFrom') && $request->query->get('lsDateFrom') ? \DateTime::createFromFormat('Y-m-d', $request->query->get('lsDateFrom')): null;
        $lsDateTo = $request->query->has('lsDateTo') && $request->query->get('lsDateTo') ? \DateTime::createFromFormat('Y-m-d', $request->query->get('lsDateTo')): new \DateTime();
        $groupByType = $request->query->has('lsGroupByType') && $request->query->get('lsGroupByType')? (int) $request->query->get('lsGroupByType') : ConfigType::getGroupByTypeDefaultOption();

        $dbData = $statisticsModel->getPayments($lsDateFrom, $lsDateTo, $groupByType);

        $formConfig = $this->createForm(ConfigType::class, [
            'addGroupByType' => true
        ]);

        $form = $this->createForm(ActiveClientsType::class);
        $form->handleRequest($request);
        if ($dbData && $form->isSubmitted() && $form->isValid()) {
            $headers = [
                'Data',
                'Wartość wpłat',
            ];
            $data = [];
            $lp = 1;
            foreach ($dbData['data'] as $row) {
                $data[] = [
                    $row['type'],
                    $row['count'],
                ];
            }

            $this->downloadSpreadsheetAsXlsx($this->createSpreadsheet($headers, $data));
        }

        $data = [
            'dbData' => $dbData
        ];

        return $this->render('@WecodersEnergyBundle/default/management.html.twig', [
            'title' => 'Wpłaty klientów',
            'dbData' => $dbData,
            'form' => $form->createView(),
            'formConfig' => $formConfig->createView(),
            'chartTemplate' => $chartTemplate,
        ]);
    }

    /**
     * @Route("/management-planned-revenues", name="managementPlannedRevenues")
     */
    public function getManagementPlannedRevenuesAction(
        Request $request,
        RequestStack $requestStack,
        EntityManager $em,
        StatisticsModel $statisticsModel,
        ValidateRoleAccess $validateRoleAccess
    )
    {
        $chartTemplate = '@WecodersEnergyBundle/charts/mixed-daily-and-intra-day-chart.html.twig';

        $lsDateFrom = $request->query->has('lsDateFrom') && $request->query->get('lsDateFrom') ? \DateTime::createFromFormat('Y-m-d', $request->query->get('lsDateFrom')): null;
        $lsDateTo = $request->query->has('lsDateTo') && $request->query->get('lsDateTo') ? \DateTime::createFromFormat('Y-m-d', $request->query->get('lsDateTo')): null;

        $dbData = $statisticsModel->getPlannedRevenues($lsDateFrom, $lsDateTo);

        $formConfig = $this->createForm(ConfigType::class);

        $form = $this->createForm(ActiveClientsType::class);

        try {
            $validateRoleAccess->validateAccess('ROLE_SUPERADMIN', $this->getUser());
            $form = $this->createForm(ActiveClientsType::class, [
                'addButtons' => [
                    [
                        'name' => 'downloadRawDataXlsx',
                        'label' => 'Pobierz dane bazowe xlsx'
                    ]
                ]
            ]);
        } catch (\Exception $e) {}

        $form->handleRequest($request);

        if ($dbData && $form->isSubmitted() && $form->isValid() && $form->get('downloadXlsx')->isClicked()) {
            $headers = [
                'Data',
                'Wartość',
            ];
            $data = [];
            $lp = 1;
            foreach ($dbData['data'] as $row) {
                $data[] = [
                    $row['type'],
                    $row['count'],
                ];
            }

            $this->downloadSpreadsheetAsXlsx($this->createSpreadsheet($headers, $data));
        } elseif ($dbData && $form->isSubmitted() && $form->isValid() && $form->get('downloadRawDataXlsx')->isClicked()) {
            $headers = [
                'Lp.',
                'ID klienta',
                'Proforma id',
                'Proforma numer',
                'Proforma okres rozliczeniowy od',
                'Proforma okres rozliczeniowy do',
                'Proforma termin płatności',
                'Proforma wartość',
                'Korekta id',
                'Korekta numer',
                'Korekta okres rozliczeniowy od',
                'Korekta okres rozliczeniowy do',
                'Korekta termin płatności',
                'Korekta wartość',
                'Typ',
                'Użyto - wartość',
                'Użyto - okres rozliczeniowy od',
                'Użyto - okres rozliczeniowy do',
            ];
            $data = [];
            $lp = 1;

            foreach ($dbData['data'] as $rowData) {
                foreach ($rowData['raw'] as $row) {
                    $data[] = [
                        $lp++,
                        $row['client_id'],
                        $row['id'],
                        $row['number'],
                        $row['billing_period_from'],
                        $row['billing_period_to'],
                        $row['date_of_payment'],
                        $row['summary_gross_value'],
                        $row['correction_id'],
                        $row['correction_number'],
                        $row['correction_billing_period_from'],
                        $row['correction_billing_period_to'],
                        $row['correction_date_of_payment'],
                        $row['correction_summary_gross_value'],
                        $row['correction_id'] ? 'Korekta' : 'Faktura proforma',
                        $row['used_summary_gross_value'],
                        $row['used_billing_period_from'],
                        $row['used_billing_period_to'],
                    ];
                }
            }

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment;filename="dane.csv"');
            header('Cache-Control: max-age=0');

            $writer = IOFactory::createWriter($this->createSpreadsheet($headers, $data), 'Csv');
            $writer->setDelimiter(';');
            $writer->save('php://output');
            exit;
        }

        $data = [
            'dbData' => $dbData
        ];

        return $this->render('@WecodersEnergyBundle/default/management.html.twig', [
            'title' => 'Planowane przychody faktury proforma',
            'dbData' => $dbData,
            'form' => $form->createView(),
            'formConfig' => $formConfig->createView(),
            'chartTemplate' => $chartTemplate,
        ]);
    }

    /**
     * @Route("/management-planned-revenues-from-created-documents", name="managementPlannedRevenuesFromCreatedDocuments")
     */
    public function getManagementPlannedRevenuesFromCreatedDocumentsAction(Request $request, RequestStack $requestStack, EntityManager $em, StatisticsModel $statisticsModel)
    {
        $chartTemplate = '@WecodersEnergyBundle/charts/mixed-daily-and-intra-day-chart.html.twig';

        $lsDateFrom = $request->query->has('lsDateFrom') && $request->query->get('lsDateFrom') ? \DateTime::createFromFormat('Y-m-d', $request->query->get('lsDateFrom')): null;
        $lsDateTo = $request->query->has('lsDateTo') && $request->query->get('lsDateTo') ? \DateTime::createFromFormat('Y-m-d', $request->query->get('lsDateTo')): null;

        $dbData = $statisticsModel->getPlannedRevenuesFromCreatedDocuments($lsDateFrom, $lsDateTo);

        $formConfig = $this->createForm(ConfigType::class);

        $form = $this->createForm(ActiveClientsType::class, [
            'addButtons' => [
                [
                    'name' => 'downloadRawDataXlsx',
                    'label' => 'Pobierz dane bazowe xlsx'
                ]
            ]
        ]);
        $form->handleRequest($request);


        if ($dbData && $form->isSubmitted() && $form->isValid() && $form->get('downloadXlsx')->isClicked()) {
            $headers = [
                'Data',
                'Wartość',
            ];
            $data = [];
            $lp = 1;
            foreach ($dbData['data'] as $row) {
                $data[] = [
                    $row['type'],
                    $row['count'],
                ];
            }

            $this->downloadSpreadsheetAsXlsx($this->createSpreadsheet($headers, $data));
        } elseif ($dbData && $form->isSubmitted() && $form->isValid() && $form->get('downloadRawDataXlsx')->isClicked()) {
            $headers = [
                'Lp.',
                'Faktura proforma id',
                'Faktura proforma numer',
                'Faktura proforma termin płatności',
                'Faktura proforma wartość',
                'Faktura proforma korekta id',
                'Faktura proforma korekta numer',
                'Faktura proforma korekta termin płatności',
                'Faktura proforma korekta wartość',
                'Typ',
            ];
            $data = [];
            $lp = 1;
            foreach ($dbData['raw'] as $row) {
                $data[] = [
                    $lp++,
                    $row['id'],
                    $row['number'],
                    $row['date_of_payment'],
                    $row['summary_gross_value'],
                    $row['correction_id'],
                    $row['correction_number'],
                    $row['correction_date_of_payment'],
                    $row['correction_summary_gross_value'],
                    $row['correction_id'] ? 'Korekta' : 'Faktura proforma',
                ];
            }

            $this->downloadSpreadsheetAsXlsx($this->createSpreadsheet($headers, $data));
        }

        $data = [
            'dbData' => $dbData
        ];

        return $this->render('@WecodersEnergyBundle/default/management.html.twig', [
            'title' => 'Wpłaty klientów',
            'dbData' => $dbData,
            'form' => $form->createView(),
            'formConfig' => $formConfig->createView(),
            'chartTemplate' => $chartTemplate,
        ]);
    }

    private function downloadSpreadsheetAsXlsx($spreadsheet)
    {
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="dane.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    private function createSpreadsheet($headers, $data)
    {
        $spreadsheet = new Spreadsheet();

        $column = 1;
        $row = 1;

        foreach ($headers as $cell) {
            $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow($column++, $row, $cell);
        }

        $row++;

        $columnsCount = count($headers);
        foreach ($data as $item) {
            for ($i = 1; $i <= $columnsCount; $i++) {
                $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow($i, $row, $item[$i - 1]);
            }
            $row++;
        }

        return $spreadsheet;
    }


}
