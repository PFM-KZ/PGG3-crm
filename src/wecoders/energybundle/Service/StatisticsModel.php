<?php

namespace Wecoders\EnergyBundle\Service;

use DateInterval;
use DatePeriod;
use DateTime;
use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\InvoiceProforma;
use GCRM\CRMBundle\Entity\StatusContract;
use GCRM\CRMBundle\Entity\StatusDepartment;
use GCRM\CRMBundle\Service\PaymentModel;
use GCRM\CRMBundle\Service\StatusContractModel;
use GCRM\CRMBundle\Service\StatusDepartmentModel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Wecoders\EnergyBundle\Form\Statistics\ConfigType;

class StatisticsModel
{
    private $em;
    private $statusDepartmentModel;
    private $statusContractModel;
    private $paymentModel;

    public function __construct(EntityManager $em, StatusDepartmentModel $statusDepartmentModel, StatusContractModel $statusContractModel, PaymentModel $paymentModel)
    {
        $this->em = $em;
        $this->statusDepartmentModel = $statusDepartmentModel;
        $this->statusContractModel = $statusContractModel;
        $this->paymentModel = $paymentModel;
    }

    public function getPlannedRevenuesFromCreatedDocuments($from, $to)
    {
        $conn = $this->em->getConnection();

        $sqlWhereParts = [];
        if ($from) {
            $sqlWhereParts[] = ' (a.date_of_payment >= "' . $from->format('Y-m-d') . ' 00:00:00" OR b.date_of_payment >= "' . $from->format('Y-m-d') . ' 00:00:00") ';
        }
        if ($to) {
            $tmpTo = (clone $to)->setTime(0, 0)->modify('+ 1 day');
            $sqlWhereParts[] = ' (a.date_of_payment < "' . $tmpTo->format('Y-m-d') . ' 00:00:00" OR b.date_of_payment < "' . $tmpTo->format('Y-m-d') . ' 00:00:00") ';
        }

        $sqlWhereFinal = '';
        if (count($sqlWhereParts)) {
            $sqlWhereFinal = 'WHERE ' . implode(' AND ', $sqlWhereParts);
        }

        $sql = '
SELECT 
  a.id, a.number, a.date_of_payment, a.summary_gross_value,
  b.id as correction_id, b.number as correction_number, b.date_of_payment as correction_date_of_payment, b.summary_gross_value as correction_summary_gross_value
FROM `invoice_proforma_energy` a
LEFT JOIN `invoice_proforma_correction_energy` b 
ON b.invoice_id = a.id
' . $sqlWhereFinal . '
ORDER BY a.date_of_payment ASC, b.date_of_payment DESC
';

        // AND c.status_contract_finances_id IS NULL
        // AND (c.status_contract_finances_id IS NULL OR c.status_contract_finances_id = 41 OR c.status_contract_finances_id = 40 OR c.status_contract_finances_id = 44 OR c.status_contract_finances_id = 2)

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();

        $groupByFormat = 'Y-m';

        $groupBy = [];
        foreach ($result as $item) {
            $summaryGrossValue = $item['correction_id'] ? $item['correction_summary_gross_value'] : $item['summary_gross_value'];
            // Y-m-d format
            $dateOfPayment = $item['correction_id'] ? $item['correction_date_of_payment'] : $item['date_of_payment'];
            $dateOfPayment = \DateTime::createFromFormat('Y-m-d', $dateOfPayment);

            if (!array_key_exists($dateOfPayment->format($groupByFormat), $groupBy)) {
                $groupBy[$dateOfPayment->format($groupByFormat)] = [
                    'sum' => 0,
                    'raw' => [],
                ];
            }

            $groupBy[$dateOfPayment->format($groupByFormat)]['sum'] += $summaryGrossValue;
            $groupBy[$dateOfPayment->format($groupByFormat)]['raw'][] = $item;
        }

        // manage from
        if (!$from && $result) {
            $dateOfPayment = $result[0]['correction_id'] ? $result[0]['correction_date_of_payment'] : $result[0]['date_of_payment'];
            $dateOfPayment = \DateTime::createFromFormat('Y-m-d', $dateOfPayment);

            $from = ($dateOfPayment)->setTime(0, 0);
        }

        // manage to
        if (!$to && $result) {
            $dateOfPayment = $result[count($result) - 1]['correction_id'] ? $result[count($result) - 1]['correction_date_of_payment'] : $result[count($result) - 1]['date_of_payment'];
            $dateOfPayment = \DateTime::createFromFormat('Y-m-d', $dateOfPayment);

            $to = ($dateOfPayment)->setTime(0, 0);
        }

        $fromTmp = (clone $from)->modify('first day of this month')->setTime(0, 0);
        $toTmp = (clone $to)->modify('first day of this month')->setTime(0, 0);
        $dateMap = $this->createDateMap($fromTmp, $toTmp, 'P1M');

        if ($dateMap) {
            $tmpResult = [];
            // apply records to map
            foreach ($dateMap as $date) {
                if ($groupBy && array_key_exists($date->format($groupByFormat), $groupBy)) {
                    $tmpResult[$date->format($groupByFormat)] = $groupBy[$date->format($groupByFormat)];
                } else {
                    $tmpResult[$date->format($groupByFormat)]['sum'] = 0;
                    $tmpResult[$date->format($groupByFormat)]['raw'] = [];
                }
            }

            $groupBy = $tmpResult;
        }

        // hydrate
        $data = [];
        if ($groupBy) {
            foreach ($groupBy as $date => $record) {
                $data[] = [
                    'type' => $date,
                    'count' => $record['sum'],
                ];
            }
        }

        $config = [
            'X' => 'type',
            'Y' => 'count'
        ];

        return [
            'config' => $config,
            'data' => $data,
            'raw' => $result
        ];
    }

    public function getPlannedRevenues($from, $to)
    {
        $conn = $this->em->getConnection();

        $sqlWhereParts = [];
        if ($from) {
            $sqlWhereParts[] = ' (a.date_of_payment >= "' . $from->format('Y-m-d') . ' 00:00:00" OR b.date_of_payment >= "' . $from->format('Y-m-d') . ' 00:00:00") ';
        }
        if ($to) {
            $tmpTo = (clone $to)->setTime(0, 0)->modify('+ 1 day');
            $sqlWhereParts[] = ' (a.date_of_payment < "' . $tmpTo->format('Y-m-d') . ' 00:00:00" OR b.date_of_payment < "' . $tmpTo->format('Y-m-d') . ' 00:00:00") ';
        }

        $sqlWhereFinal = '';
        if (count($sqlWhereParts)) {
            $sqlWhereFinal = 'WHERE ' . implode(' AND ', $sqlWhereParts);
        }

        $sql = '
SELECT 
  a.id, a.number, a.date_of_payment, a.summary_gross_value, a.billing_period_from, a. billing_period_to,
  b.id as correction_id, b.number as correction_number, b.date_of_payment as correction_date_of_payment, b.summary_gross_value as correction_summary_gross_value, b.billing_period_from as correction_billing_period_from, b. billing_period_to as correction_billing_period_to,
  c.id as client_id
FROM `invoice_proforma_energy` a
LEFT JOIN `client` c
ON a.client_id = c.id
LEFT JOIN `invoice_proforma_correction_energy` b 
ON b.invoice_id = a.id
' . $sqlWhereFinal . '
ORDER BY a.date_of_payment ASC, b.date_of_payment DESC
';

        // AND c.status_contract_finances_id IS NULL
        // AND (c.status_contract_finances_id IS NULL OR c.status_contract_finances_id = 41 OR c.status_contract_finances_id = 40 OR c.status_contract_finances_id = 44 OR c.status_contract_finances_id = 2)

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();





        $firstDayOfThisMonth = (new \DateTime())->modify('first day of this month')->setTime(0, 0);
        $firstDayOfNextMonth = (new \DateTime())->modify('first day of next month')->setTime(0, 0);

        // filter result, remove created documents that year and month are higher that current month
        $newResult = [];
        foreach ($result as $item) {
            $dateOfPayment = $item['correction_id'] ? $item['correction_date_of_payment'] : $item['date_of_payment'];
            $dateOfPayment = \DateTime::createFromFormat('Y-m-d', $dateOfPayment);

            if ($dateOfPayment < $firstDayOfNextMonth) {
                $newResult[] = $item;
            }
        }
        $result = $newResult;


        // create virtual records by contract created from date and period in month, only for above current month
        $energy = $this->getActiveClientsByContractType('ENERGY');
        $gas = $this->getActiveClientsByContractType('GAS');
        $virtualRecordsEnergy = $this->createVirtualRecords($energy, $firstDayOfNextMonth);
        $virtualRecordsGas = $this->createVirtualRecords($gas, $firstDayOfNextMonth);

        foreach ($result as &$item) {
            $prefix = $item['correction_id'] ? 'correction_' : '';
            $item['used_summary_gross_value'] = $item[$prefix . 'summary_gross_value'];
            $item['used_billing_period_from'] = $item[$prefix . 'billing_period_from'];
            $item['used_billing_period_to'] = $item[$prefix . 'billing_period_to'];
        }

        // merge records from db with virtual records
        $result = array_merge($result, $virtualRecordsEnergy, $virtualRecordsGas);



        $groupByFormat = 'Y-m';

        $groupBy = [];
        foreach ($result as $item) {
            $summaryGrossValue = $item['correction_id'] ? $item['correction_summary_gross_value'] : $item['summary_gross_value'];
            // Y-m-d format
            $dateOfPayment = $item['correction_id'] ? $item['correction_date_of_payment'] : $item['date_of_payment'];
            $dateOfPayment = \DateTime::createFromFormat('Y-m-d', $dateOfPayment);

            if (!array_key_exists($dateOfPayment->format($groupByFormat), $groupBy)) {
                $groupBy[$dateOfPayment->format($groupByFormat)] = [
                    'sum' => 0,
                    'raw' => [],
                ];
            }

            $groupBy[$dateOfPayment->format($groupByFormat)]['sum'] += $summaryGrossValue;
            $groupBy[$dateOfPayment->format($groupByFormat)]['raw'][] = $item;
        }

        // manage from
        if (!$from && $result) {
            $dateOfPayment = $result[0]['correction_id'] ? $result[0]['correction_date_of_payment'] : $result[0]['date_of_payment'];
            $dateOfPayment = \DateTime::createFromFormat('Y-m-d', $dateOfPayment);

            $from = ($dateOfPayment)->setTime(0, 0);
        }

        // manage to
        if (!$to && $result) {
            $dateOfPayment = $result[count($result) - 1]['correction_id'] ? $result[count($result) - 1]['correction_date_of_payment'] : $result[count($result) - 1]['date_of_payment'];
            $dateOfPayment = \DateTime::createFromFormat('Y-m-d', $dateOfPayment);

            $to = ($dateOfPayment)->setTime(0, 0);
        }

        $dateMap = null;
        if ($result) {
            $fromTmp = (clone $from)->modify('first day of this month')->setTime(0, 0);
            $toTmp = (clone $to)->modify('first day of this month')->setTime(0, 0);
            $dateMap = $this->createDateMap($fromTmp, $toTmp, 'P1M');
        }

        if ($dateMap) {
            $tmpResult = [];
            // apply records to map
            foreach ($dateMap as $date) {
                if ($groupBy && array_key_exists($date->format($groupByFormat), $groupBy)) {
                    $tmpResult[$date->format($groupByFormat)] = $groupBy[$date->format($groupByFormat)];
                } else {
                    $tmpResult[$date->format($groupByFormat)]['sum'] = 0;
                    $tmpResult[$date->format($groupByFormat)]['raw'] = [];
                }
            }

            $groupBy = $tmpResult;
        }


        // hydrate
        $data = [];
        $summary = 0;
        if ($groupBy) {
            foreach ($groupBy as $date => $record) {
                $data[] = [
                    'type' => $date,
                    'count' => $record['sum'],
                    'raw' => $record['raw'],
                ];
                $summary += $record['sum'];
            }
        }

        $config = [
            'X' => 'type',
            'Y' => 'count'
        ];

        return [
            'config' => $config,
            'data' => $data,
            'raw' => $result,
            'summary' => $summary,
        ];
    }

    public function createVirtualRecords(&$records, $fromDate)
    {
        $virtualRecords = [];
        foreach ($records as $item) {
            if (!$item['contract_from_date']) {
                continue;
            }

            if (!$item['period_in_month']) {
                continue;
            }

            if (!is_numeric($item['period_in_month'])) {
                continue;
            }
            $item['period_in_month'] = (int) $item['period_in_month'];

            $dateStart = \DateTime::createFromFormat('Y-m-d', $item['contract_from_date']);
            $dateStart->setTime(0, 0);
            $tmpDateToManage = clone $dateStart;

            // get first proforma of user and check if correction exist
            $qb = $this->em->createQueryBuilder();
            $q = $qb->select(['a'])
                ->from('WecodersEnergyBundle:InvoiceProforma', 'a')
                ->where('a.client = :clientId')
                ->setParameters(['clientId' => $item['id']])
                ->orderBy('a.dateOfPayment', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
            ;
            $invoiceProformas = $q->getResult();
            if (!$invoiceProformas) {
                continue;
            }

            /** @var InvoiceProforma $invoiceProforma */
            $invoiceProforma = $invoiceProformas[0];
            $summaryGrossValue = $invoiceProforma->getSummaryGrossValue();


            // adds
            $beforeDate = null;
            for ($i = 0; $i < $item['period_in_month']; $i++) {
                if (!$beforeDate) { // first month
                    $dateToAdd = clone $tmpDateToManage;
                } else {
                    $tmpDateToManage->modify('+1 month');
                    $dateToAdd = clone $beforeDate;
                }

                // omit records that are lower than first day of next month
                if ($dateToAdd < $fromDate) {
                    $beforeDate = (clone $tmpDateToManage);
                    continue;
                }

                $billingPeriodFromAsString = (clone $dateToAdd)->modify('first day of this month')->format('Y-m-d');
                $billingPeriodToAsString = (clone $dateToAdd)->modify('last day of this month')->format('Y-m-d');
                $virtualRecords[] = [
                    "client_id" => $item['id'],
                    "id" => "-",
                    "number" => "-",
                    "date_of_payment" => $dateToAdd->format('Y-m-d'),
                    "summary_gross_value" => $summaryGrossValue,
                    "billing_period_from" => $billingPeriodFromAsString,
                    "billing_period_to" => $billingPeriodToAsString,
                    "correction_id" => null,
                    "correction_number" => null,
                    "correction_date_of_payment" => null,
                    "correction_summary_gross_value" => null,
                    "correction_billing_period_from" => null,
                    "correction_billing_period_to" => null,
                    "used_summary_gross_value" => $summaryGrossValue,
                    "used_billing_period_from" => $billingPeriodFromAsString,
                    "used_billing_period_to" => $billingPeriodToAsString,
                ];

                $beforeDate = (clone $tmpDateToManage);
            }
        }

        return $virtualRecords;
    }

    public function getPayments($from, $to, $groupBy)
    {
        $payments = $this->paymentModel->getRecordsByDates($from, $to);

        // manage from
        if (!$from && $payments) {
            $from = ($payments[0]->getDate())->setTime(0, 0);
        }

        // manage to
        if (!$to && $payments) {
            $to = ($payments[count($payments) - 1]->getDate())->setTime(0, 0);
        }

        $dateMap = null;
        if ($groupBy == ConfigType::GROUP_BY_TYPE_MONTH) {
            $groupByFormat = 'Y-m';
            if ($from && $to) {
                $dateMap = $this->createDateMap($from, $to, 'P1M');
            }
        } elseif ($groupBy == ConfigType::GROUP_BY_TYPE_DAY) {
            $groupByFormat = 'Y-m-d';
            if ($from && $to) {
                $dateMap = $this->createDateMap($from, $to);
            }
        } else {
            throw new \RuntimeException();
        }

        $groupBy = $this->groupByDateFormat($payments, 'getDate', $groupByFormat);

        if ($dateMap) {
            $result = [];
            // apply records to map
            foreach ($dateMap as $date) {
                if ($groupBy && array_key_exists($date->format($groupByFormat), $groupBy)) {
                    $result[$date->format($groupByFormat)] = $groupBy[$date->format($groupByFormat)];
                } else {
                    $result[$date->format($groupByFormat)]['raw'] = [];
                }
            }

            $groupBy = $result;
        }






        // hydrate
        $data = [];
        $summary = 0;
        if ($groupBy) {
            $this->sumGroupedValues($groupBy, 'getValue');
            foreach ($groupBy as $date => $record) {
                $data[] = [
                    'type' => $date,
                    'count' => $record['sum'],
                ];
                $summary += $record['sum'];
            }
        }

        $config = [
            'X' => 'type',
            'Y' => 'count'
        ];

        return [
            'config' => $config,
            'data' => $data,
            'summary' => $summary,
        ];
    }

    public function createDateMap(\DateTime $from, \DateTime $to, $intervalSpec = 'P1D', $format = null)
    {
        $period = new DatePeriod(
            $from,
            new DateInterval($intervalSpec),
            $to->modify('+1 day')
        );

        $dates = [];
        if ($format) {
            foreach ($period as $date) {
                $dates[] = $date->format($format);
            }
        } else {
            foreach ($period as $date) {
                $dates[] = $date;
            }
        }

        return $dates;
    }

    public function sumGroupedValues(&$records, $getValueMethodName)
    {
        if (!$records) {
            return;
        }

        foreach ($records as $groupedKey => $groupRecords) {
            $sum = 0;

            foreach ($groupRecords['raw'] as $key => $record) {
                $sum += $record->$getValueMethodName();
            }

            $records[$groupedKey]['sum'] = $sum;
        }
    }

    public function groupByDateFormat(&$records, $getDateMethodName, $dateFormat)
    {
        if (!$records) {
            return null;
        }

        $result = [];

        foreach ($records as $record) {
            /** @var \DateTime $date */
            $date = $record->$getDateMethodName();
            $format = $date->format($dateFormat);

            if (!array_key_exists($format, $result)) {
                $result[$format]['raw'] = [];
            }

            $result[$format]['raw'][] = $record;
        }

        return $result;
    }

    public function getActiveClients()
    {
        $energy = $this->getActiveClientsByContractType('ENERGY');
        $gas = $this->getActiveClientsByContractType('GAS');

        $config = [
            'X' => 'type',
            'Y' => 'count',
        ];

        $data = [
            [
                'type' => 'Prąd',
                'count' => count($energy)
            ],
            [
                'type' => 'Gaz',
                'count' => count($gas)
            ]
        ];

        return [
            'config' => $config,
            'data' => $data,
            'raw' => [
                'ENERGY' => $energy,
                'GAS' => $gas,
            ],
        ];
    }

    private function getActiveClientsByContractType($contractType)
    {
        /** @var StatusDepartment $statusDepartmentFinances */
        $statusDepartmentFinances = $this->statusDepartmentModel->getRecordByCode(StatusDepartmentModel::DEPARTMENT_FINANCES_CODE);
        if (!$statusDepartmentFinances) {
            throw new \Exception('Status finances department not found.');
        }

        // fetch statuses ids to search for
        $ids = [];
        $statusContracts = $this->statusContractModel->getStatusContractsBySpecialActionOption(StatusContractModel::SPECIAL_ACTION_ACTIVE_CLIENT);
        /** @var StatusContract $statusContract */
        foreach ($statusContracts as $statusContract) {
            $ids[] = $statusContract->getId();
        }

        if ($contractType == 'ENERGY') {
            return $this->getActiveClientsByData(
                'link_client_and_contract_energy',
                'contract_energy',
                $statusDepartmentFinances->getId(),
                $ids
            );
        } elseif ($contractType == 'GAS') {
            return $this->getActiveClientsByData(
                'link_client_and_contract_gas',
                'contract_gas',
                $statusDepartmentFinances->getId(),
                $ids
            );
        }

        throw new \Exception('Invalid contract type.');
    }

    private function getActiveClientsByData($clientAndContractsEntity, $contractsEntity, $fromDepartamentId, $statusContractIds)
    {
        $conn = $this->em->getConnection();

        $financesSqlParts = [];
        $financesSqlParts[] = 'c.status_contract_finances_id IS NULL';
        foreach ($statusContractIds as $id) {
            $financesSqlParts[] = 'c.status_contract_finances_id = ' . $id;
        }
        $financesSqlMerged = '(' . implode(' OR ', $financesSqlParts) . ')';

        $sql = '
SELECT
  a.id, a.name, a.surname, a.city,
  ani.number as badge_id,
  c.tariff_id, c.brand_id, c.type, c.consumption, c.period_in_month, c.contract_from_date,
  d.code as tariff_code, d.title as tariff_title,
  brand.title as brand_title
FROM `client` a
LEFT JOIN `account_number_identifier` as ani
  ON a.account_number_identifier_id = ani.id
LEFT JOIN `' . $clientAndContractsEntity . '` as lc
  ON a.id = lc.client_id
LEFT JOIN `' . $contractsEntity . '` as c
  ON lc.contract_id = c.id
LEFT JOIN `tariff_energy` as d
  ON c.tariff_id = d.id
LEFT JOIN `brand_energy` as brand
  ON c.brand_id = brand.id
WHERE
  c.id IS NOT NULL
  AND c.status_department_id = ' . $fromDepartamentId . '
  AND '. $financesSqlMerged .'
  AND c.is_resignation != 1
  AND c.is_broken_contract != 1
';

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}