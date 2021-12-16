<?php

namespace GCRM\CRMBundle\Service\BillingDocument;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\StatusDepartment;
use GCRM\CRMBundle\Service\EasyAdminModel;
use GCRM\CRMBundle\Service\PaymentModel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Entity\BillingDocumentInterface;
use Wecoders\EnergyBundle\Model\SettlementIncludedDocument;
use Wecoders\InvoiceBundle\Service\InvoiceModel;

class Initializer
{
    const STRUCTURE_NAME_INVOICE_PROFORMA = 'invoiceProforma';
    const STRUCTURE_NAME_INVOICE_PROFORMA_CORRECTION = 'invoiceProformaCorrection';

    private $container;

    private $em;

    private $easyAdminModel;

    private $paymentModel;

    private $client;

    private $invoiceModel;

    private $mostActiveSettlementDocument;

    private $invoicesIncludedInSettlements;

    private $classes = [];

    private $structure = [
        'balance' => [
            'total' => 0,
            'toPay' => 0,
            'paid' => 0,
            'initial' => 0,
            'overpaid' => 0,
        ],
        'payments' => [],
        'data' => null
    ];

    public function getStructure()
    {
        return $this->structure;
    }

    public function __construct(EntityManager $em, ContainerInterface $container, EasyAdminModel $easyAdminModel, InvoiceModel $invoiceModel, PaymentModel $paymentModel)
    {
        $this->em = $em;
        $this->container = $container;
        $this->easyAdminModel = $easyAdminModel;
        $this->invoiceModel = $invoiceModel;
        $this->paymentModel = $paymentModel;

    }

    public function init(Client $client)
    {
        $this->client = $client;
        $this->structure['payments'] = $this->paymentModel->getPaymentsByNumber($client->getAccountNumberIdentifier()->getNumber(), 'DESC');
        $this->structure['balance']['paid'] = $this->paymentModel->calculateSummaryFromPayments($this->structure['payments']);
        $this->setInitialBalance($client->getInitialBalance());
        $this->structure['balance']['paid'] += $this->structure['balance']['initial'];
        $this->structure['balance']['total'] = 0;
        $this->structure['balance']['toPay'] = 0;
        $this->structure['balance']['overpaid'] = 0;

        $settings = $this->container->getParameter('billing_document');
        foreach ($settings as $item) {
            $class = new $item['class']($this->em, $item);
            $this->structure['data'][$item['name']]['settings'] = $item;
            $this->structure['data'][$item['name']]['title'] = $item['title'];
            $this->structure['data'][$item['name']]['label'] = $item['label'];
            $this->structure['data'][$item['name']]['class'] = $item['class'];
            $this->structure['data'][$item['name']]['object'] = $class;
            $this->classes[] = $class;
        }

        return $this;
    }

    /**
     * Get all documents ordered by date of payment ASC (from oldest to newest)
     */
    public function getDocumentsBag()
    {
        $bag = [];

        // put all records in a bag
        foreach ($this->structure['data'] as $key => $data) {
            $records = $data['records'];
            if ($records && count($records)) {
                $bag = array_merge($bag, $records);
            }
        }

        // no documents, return null
        if (!count($bag)) {
            return null;
        }

        // do not need to sort, return as it is
        if (count($bag) == 1) {
            return $bag;
        }

        // order records by date of payment
        usort($bag, function($a, $b) {
            $aDate = $a->getDateOfPayment() ? $a->getDateOfPayment()->getTimestamp() : null;
            $bDate = $b->getDateOfPayment() ? $b->getDateOfPayment()->getTimestamp() : null;

            return strcmp($aDate, $bDate);
        });

        return $bag;
    }

    /**
     * Frozen value is balance that appear in documents like corrections or settlements.
     * When document is made, it goes at the top of the stack. When it comes to corrections, those documents need to hold value
     * from their original document (Max value of: original document paid value and summary gross value of correction)
     * Settlements need to hold paid value of proforma documents.
     *
     * @param array $documents
     */
    public function getDocumentsFrozenValue($documents)
    {
        $frozenValue = 0;
        if (!$documents) {
            return 0;
        }

        /** @var BillingDocumentInterface $document */
        foreach ($documents as $document) {
            if ($document->getIsNotActual()) {
                continue;
            }

            $frozenValue += $document->getFrozenValue();
        }

        return $frozenValue;
    }

    /**
     * @param array $documents
     */
    public function getDocumentsPaidValue($documents)
    {
        $paidValue = 0;
        if (!$documents) {
            return 0;
        }

        /** @var BillingDocumentInterface $document */
        foreach ($documents as $document) {
            if ($document->getIsNotActual()) {
                continue;
            }

            $paidValue += $document->getPaidValue();
        }

        return $paidValue;
    }

    /**
     * It can be settlement or estimated settlement, it can be also correction of those
     */
    private function setMostActiveSettlementDocument()
    {
        $mostActiveSettlementDocument = null;

        // gets most actual settlement
        foreach ($this->structure['data'] as $key => $item) {
            if (!$item['settings']['isSettlement']) {
                continue;
            }

            if ($item['settings']['isCorrection']) {
                continue;
            }

            /** @var DocumentInterface $itemObject */
            $itemObject = $item['object'];
            $settlements = $itemObject->getDocumentRowsByClientId($this->client);
            if (!$settlements) {
                continue;
            }

            /** @var DocumentInterface $settlement */
            foreach ($settlements as $settlement) {
                $mostActiveCorrection = $this->getMostActiveCorrectionFromDocument($settlement);
                if ($mostActiveCorrection) {
                    if ($mostActiveSettlementDocument === null || ($mostActiveCorrection->getBillingPeriodTo() && $mostActiveCorrection->getBillingPeriodTo() > $mostActiveSettlementDocument->getBillingPeriodTo())) {
                        $mostActiveSettlementDocument = $mostActiveCorrection;
                    }
                } else {
                    if ($mostActiveSettlementDocument === null || ($settlement->getBillingPeriodTo() && $settlement->getBillingPeriodTo() > $mostActiveSettlementDocument->getBillingPeriodTo())) {
                        $mostActiveSettlementDocument = $settlement;
                    }
                }
            }
        }

        $this->mostActiveSettlementDocument = $mostActiveSettlementDocument;
    }

    public function getMostActiveSettlementDocument()
    {
        return $this->mostActiveSettlementDocument;
    }

    public function getMostActiveCorrectionFromDocument($document)
    {
        $corrections = $document->getCorrections();
        $mostActive = null;

        if ($corrections) {
            foreach ($corrections as $correction) {
                if (!$mostActive) {
                    $mostActive = $correction;
                }

                if ($correction->getCreatedAt() > $mostActive->getCreatedAt()) {
                    $mostActive = $correction;
                }
            }
        }

        return $mostActive;
    }

    public function invoiceDirDataPiece($invoice)
    {
        $datePieces = explode('-', $invoice->getCreatedDate()->format('Y-m-d'));
        return $datePieces[0] . '/' . $datePieces[1];
    }

    public function getInvoicesIncludedInSettlements()
    {
        $result = [];

        $documents = $this->fetchInvoicesBySettingsName('invoiceSettlement');
        if ($documents) {
            $merged = $this->fetchInvoicesIncludedFromDocuments($documents);
            $result = array_merge($result, $merged);
        }

        $documents = $this->fetchInvoicesBySettingsName('invoiceEstimatedSettlement');
        if ($documents) {
            $merged = $this->fetchInvoicesIncludedFromDocuments($documents);
            $result = array_merge($result, $merged);
        }

        return $result;
    }

    private function fetchInvoicesIncludedFromDocuments($documents)
    {
        $result = [];

        foreach ($documents as $document) {
            $mostActiveCorrection = $this->getMostActiveCorrectionFromDocument($document);

            if ($mostActiveCorrection) {
                $invoices = $mostActiveCorrection->getIncludedDocuments();
            } else {
                $invoices = $document->getIncludedDocuments();
            }

            if (!$invoices) {
                continue;
            }

            $result = array_merge($result, $invoices);
        }

        return $result;
    }

    private function fetchInvoicesBySettingsName($name)
    {
        foreach ($this->structure['data'] as $key => $item) {
            if ($item['settings']['name'] != $name) {
                continue;
            }

            /** @var DocumentInterface $itemObject */
            $itemObject = $item['object'];
            return $itemObject->getDocumentRowsByClientId($this->client);
        }

        return null;
    }

    public function generate()
    {
        $this->setMostActiveSettlementDocument();
        $this->invoicesIncludedInSettlements = $this->getInvoicesIncludedInSettlements();

        $lastDayOfCurrentMonth = new \DateTime();
        $lastDayOfCurrentMonth->modify('last day of this month');
        $lastDayOfCurrentMonth->setTime(0, 0, 0);

        foreach ($this->structure['data'] as $key => $item) {
            $isProformaType = false;
            if (isset($item['settings']['isProformaType']) && $item['settings']['isProformaType']) {
                $isProformaType = true;
            }

            // sets initial values
            $this->structure['data'][$key]['summaryGrossValue'] = 0;

            /** @var DocumentInterface $itemObject */
            $itemObject = $item['object'];
            $this->structure['data'][$key]['entityName'] = $this->easyAdminModel->getEntityNameByEntityClass($itemObject->getEntity());

            $documentRows = $itemObject->getDocumentRowsByClientId($this->client);
            if ($documentRows) {
                // manage state is generated file exists
                $documentDirectory = $this->easyAdminModel->getEntityDirectoryByEntityName($this->structure['data'][$key]['entityName']);
                foreach ($documentRows as $documentRow) {
                    $this->applyStateIsGeneratedFileExist($documentRow, $documentDirectory);
                }

                // manage state is not actual
                foreach ($documentRows as $documentRow) {
                    $this->applyStateIsNotActual($isProformaType, $documentRow, $this->mostActiveSettlementDocument, $item['settings']['isSettlement']);
                }

                // sum summaryGrossValue
                $this->structure['data'][$key]['summaryGrossValue'] = $this->calculateNotPaidSummaryGrossValueFromDocuments($documentRows);

                // balance toPay
                $this->structure['balance']['toPay'] += $this->structure['data'][$key]['summaryGrossValue'];
                $this->structure['balance']['total'] += $this->calculateSummaryGrossValueFromDocuments($documentRows);
                // calculate overpaid?
            }

            $this->structure['data'][$key]['records'] = $documentRows;
        }

        return $this;
    }

    public function generateFilenameFromNumber($number)
    {
        return str_replace('/', '-', $number);
    }

    public function applyStateIsGeneratedFileExist(&$invoice, $dir)
    {
        $filename = $this->generateFilenameFromNumber($invoice->getNumber());
        if (file_exists($dir . '/' . $this->invoiceDirDataPiece($invoice) . '/' . $filename . '.pdf')) {
            $invoice->setIsGeneratedFileExist(true);
        } else {
            $invoice->setIsGeneratedFileExist(false);
        }
    }

    private function isIncludedInSettlements(&$document)
    {
        /** @var SettlementIncludedDocument $settlementIncludedDocument */
        foreach ($this->invoicesIncludedInSettlements as $settlementIncludedDocument) {
            if ($settlementIncludedDocument->getDocumentNumber() == $document->getNumber()) {
                return true;
            }
        }

        return false;
    }

    public function applyStateIsNotActual($isProformaType, &$document, $mostActiveSettlementDocument = null, $isSettlementDocument = false)
    {
        // set not actual, if document is included in settlement
        if ($isProformaType && $mostActiveSettlementDocument) {
            if ($this->isIncludedInSettlements($document)) {
                $document->setIsNotActual(true);
                return;
            }
        }

        if (method_exists($document, 'getCorrections') && count($document->getCorrections())) {
            // if document have corrections then is not actual
            $document->setIsNotActual(true);
        } elseif (method_exists($document, 'getInvoice') && $document->getInvoice()) {
            // if document is correction (have parent document) check if this is last document of this type for this parent
            $parent = $document->getInvoice();
            $mostActiveCorrection = $this->getMostActiveCorrectionFromDocument($parent);
            if ($document->getId() != $mostActiveCorrection->getId()) {
                $document->setIsNotActual(true);
            } else {
                $document->setIsNotActual(false);
            }
        } else {
            $document->setIsNotActual(false);
        }
    }

    /**
     * Updates paid values and states of documents
     * Before this action, documents need to be prepared (init and generate functions must be called firstly)
     *
     * @return bool
     */
    public function updateDocumentsIsPaidState()
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        // gets all documents ordered properly (from oldest date of payment)
        $bag = $this->getDocumentsBag();

        // no documents, nothing to do - exit
        if (!$bag) {
            return;
        }


        $structureDocuments = $this->getStructure();
        $balancePaid = $structureDocuments['balance']['paid'];
        $freeBalance = $balancePaid;


        // throw out the prices, all of them, from every active document
        // inactive documents for history logs leave untouched - if somehow document come back to life, it will be recalculated from 0 anyway
        foreach ($bag as $document) {
            // ommit records that are not actual
            if ($document->getIsNotActual()) {
                continue;
            }

            $document->setPaidValue(0);
        }


        // try to fill documents that have frozen values (empty balloons of water),
        // fill only to that frozen value, no more
        foreach ($bag as $document) {
            // ommit documents, that do not have frozen values
            if ($document->getFrozenValue() == 0) {
                continue;
            }

            // if free balance is empty, nothing to do more, exit
            if ($freeBalance == 0) {
                break;
            }

            // ommit records that are not actual
            if ($document->getIsNotActual()) {
                continue;
            }

            $toFill = $document->getFrozenValue();

            if ((string) $freeBalance < (string) $toFill) {
                $document->setPaidValue($freeBalance);
                $freeBalance = 0;
            } else {
                $document->setPaidValue($toFill);
                $freeBalance -= $toFill;
            }
        }


        // sets new free balance that contains frozen values
        // paid value at this place is value from frozen values, can be different
        $paidValue = $this->getDocumentsPaidValue($bag);
        $freeBalance = $balancePaid - $paidValue;

        // try to fill documents
        /** @var BillingDocumentInterface $document */
        foreach ($bag as $document) {
            // ommit records that are not actual
            if ($document->getIsNotActual()) {
                continue;
            }

            if ($document->getPaidValue() < $document->getSummaryGrossValue()) {
                // paid value is lower than document value,
                // append as much as can from balance to fill the diffrence
                $toFill = $document->getSummaryGrossValue() - $document->getPaidValue();

                if ((string) $freeBalance < (string) $toFill) {
                    $document->setIsPaid(false);
                    $document->setPaidValue($document->getPaidValue() + $freeBalance);
                    $freeBalance = 0;
                } else {
                    $document->setIsPaid(true);
                    $document->setPaidValue($document->getSummaryGrossValue());
                    $freeBalance = number_format($freeBalance - $toFill, 2, '.', '');
                }
            } else {
                // paid value is equal gross value
                // in those situations balance wont change
                $document->setIsPaid(true);
            }

            $this->em->persist($document);
            $this->em->flush();
        }

        $this->em->clear();

        return true;
    }

    public function calculateSummaryGrossValueFromDocuments($documents)
    {
        $result = 0;

        if ($documents) {
            foreach ($documents as $document) {
                // calculate only active documents
                if ($document->getIsNotActual()) {
                    continue;
                }

                $result += str_replace(',', '', $document->getSummaryGrossValue());
            }
        }

        return $result;
    }

    public function calculateNotPaidSummaryGrossValueFromDocuments($documents)
    {
        $result = 0;

        if ($documents) {
            foreach ($documents as $document) {
                // calculate only active documents
                if ($document->getIsNotActual()) {
                    continue;
                }

                // only not paid documents
                if ($document->getIsPaid()) {
                    continue;
                }

                // only actual to pay documents with overdue date of payment
                if (!$document->getOverdueDateOfPayment()) {
                    continue;
                }

                $result += str_replace(',', '', $document->getSummaryGrossValue() - $document->getPaidValue());
            }
        }

        return $result;
    }

    public function setInitialBalance($initialBalance)
    {
        if ($initialBalance !== null && is_numeric($initialBalance)) {
            $this->structure['balance']['initial'] = number_format($initialBalance, 2, '.', '');
        } else {
            $this->structure['balance']['initial'] = number_format(0, 2, '.', '');
        }
    }

    public function getRecordsWithOverduePayment($documentsStructure)
    {
        $records = [];
        foreach ($documentsStructure['data'] as $data) {
            foreach ($data['records'] as $document) {
                if ($document->getIsNotActual() || $document->getIsPaid() || $document->getSummaryGrossValue() == 0) { // gets only actual documents
                    continue;
                }

                // gets overdue date of payment
                $dateStart = $document->getDateOfPayment();
                $dateStart = $dateStart->setTime(0, 0);
                $dateEnd = new \DateTime('now');

                if ($dateStart < $dateEnd) {
                    $diff = $dateStart->diff($dateEnd);
                    $diffDays = $diff->days;
                } else {
                    $diffDays = 0;
                }

                if ($diffDays > 0) {
                    $records[] = $document;
                }
            }
        }

        return $records && count($records) ? $records : null;
    }

    /**
     * Function can be used for small amount of data
     *
     * @param $clients
     * @param null $limit
     * @return array
     */
    public function filterClientsWithOverduePayment($clients, $limit = 100)
    {
        if (!$clients) {
            return null;
        }

        $result = [];

        /** @var Client $client */
        foreach ($clients as $client) {
            $documentsStructure = $this->init($client)->generate()->getStructure();

            $records = $this->getRecordsWithOverduePayment($documentsStructure);

            if ($records && count($records)) {
                $result[] = [
                    'client' => $client,
                    'data' => $records,
                ];

                if ($limit && count($result) == $limit) {
                    return $result;
                }
            }
        }

        return $result;
    }

    public function getClientsToDebitNotes($containStatusContractIds, $limit = null)
    {
        if (!is_array($containStatusContractIds) || !count($containStatusContractIds)) {
            return null;
        }

        $now = new \DateTime();
        $nowQueryFormat = "\"" . $now->format('Y-m-d') . " 00:00:00\"";
        $conn = $this->em->getConnection();

        $sql = '
SELECT 
  a.id, a.name, a.surname, a.pesel, a.telephone_nr,
  (
      CASE WHEN cg.contract_number IS NOT NULL THEN cg.contract_number
      ELSE ce.contract_number
      END
  ) as "contract_number",
  (
      CASE WHEN cg.contract_number IS NOT NULL THEN cg.contract_from_date
      ELSE ce.contract_from_date
      END
  ) as "contract_from_date",
  (
      CASE WHEN cg.contract_number IS NOT NULL THEN cg.contract_to_date
      ELSE ce.contract_to_date
      END
  ) as "contract_to_date",
  (
      CASE WHEN cg.contract_number IS NOT NULL THEN cg.period_in_month
      ELSE ce.period_in_month
      END
  ) as "period_in_month"
FROM `client` a
LEFT JOIN `link_client_and_contract_gas` as cacg
  ON a.id = cacg.client_id
LEFT JOIN `contract_gas` as cg
  ON cacg.contract_id = cg.id
LEFT JOIN `link_client_and_contract_energy` as cace
  ON a.id = cace.client_id
LEFT JOIN `contract_energy` as ce
  ON cace.contract_id = ce.id
LEFT JOIN `debit_note` as dn
  ON dn.client_id = a.id
WHERE
  dn.id IS NULL 
  AND
  (
      (cg.is_marked_to_debit_note AND cg.actual_status_contract_id IN (' . implode(",", $containStatusContractIds) . '))
      OR 
      (ce.is_marked_to_debit_note AND ce.actual_status_contract_id IN (' . implode(",", $containStatusContractIds) . '))
  )
  AND
  (
      (cacg.contract_id IS NOT NULL AND cacg.client_id IS NOT NULL)
      OR
      (cace.contract_id IS NOT NULL AND cace.client_id IS NOT NULL)
  )
GROUP BY a.id
';

//        if ($limit && is_numeric($limit)) {
//            $sql .= ' LIMIT ' . (int) $limit;
//        }

        $stmt = $conn->prepare($sql);
        $stmt->execute();

        // returns clients with overdure payment document records
        // those records include all documents active and inactive
        $result = $stmt->fetchAll();
        if (!$result) {
            return null;
        }

        // grab client ids
        $clientsIds = [];
        foreach ($result as $record) {
            if (
                (!$record['contract_from_date'] || !$record['contract_to_date']) &&
                $record['period_in_month']
            ) {
                // have only period, so can be calculated
                $clientsIds[] = $record['id'];
            } elseif (
                $record['contract_from_date'] &&
                $record['contract_to_date'] &&
                $record['period_in_month']
            ) {
                // calculate
                $dateFrom = \DateTime::createFromFormat('Y-m-d', $record['contract_from_date']);
                $dateTo = \DateTime::createFromFormat('Y-m-d', $record['contract_to_date']);

                $diff = $dateFrom->diff($dateTo);
                $months = $diff->y * 12 + $diff->m + ($diff->d ? 1 : 0);

                $monthsDiff = $record['period_in_month'] - $months;
                if ($monthsDiff) {
                    $clientsIds[] = $record['id'];
                }
            }
        }

        // get clients by ids
        $clients = $this->em->getRepository('GCRMCRMBundle:Client')->findBy(['id' => $clientsIds]);

        $data = [];
        /** @var Client $client */
        foreach ($clients as $client) {
            $data[] = [
                'client' => $client,
                'data' => null
            ];
        }

        return $data;

//        // filter clients that have to pay more than X PLN
//        if ($clients) {
//            $tmpClients = [];
//            foreach ($clients as $client) {
//                $documentsStructure = $this->init($client)->generate()->getStructure();
//
//                if ($documentsStructure['balance']['toPay'] > 5) {
//                    $tmpClients[] = $client;
//                }
//            }
//            $clients = $tmpClients;
//        }
    }


    public function getClientsWithOverduePayment(StatusDepartment $fromDepartment, $containStatusContractIds, $containStatusNotChosen = true, $limit = null)
    {
        if (!is_array($containStatusContractIds) || !count($containStatusContractIds)) {
            return null;
        }

        $statusContractAdditionalQueryPartGas = '';
        $statusContractAdditionalQueryPartEnergy = '';
        if ($containStatusNotChosen) {
            $statusContractAdditionalQueryPartGas = ' OR cg.status_contract_finances_id IS NULL ';
            $statusContractAdditionalQueryPartEnergy = ' OR ce.status_contract_finances_id IS NULL ';
        }

        $now = new \DateTime();
        $nowQueryFormat = "\"" . $now->format('Y-m-d') . " 00:00:00\"";
        $conn = $this->em->getConnection();

        $sql = '
SELECT 
  a.id, a.name, a.surname, a.pesel, a.telephone_nr,
  iproforma.id as proforma_id,
  iproformacorrection.id as proforma_correction_id,
  isettlement.id as isettlement_id,
  isettlementcorrection.id as isettlement_correction_id
FROM `client` a
LEFT JOIN `link_client_and_contract_gas` as cacg
  ON a.id = cacg.client_id
LEFT JOIN `contract_gas` as cg
  ON cacg.contract_id = cg.id
LEFT JOIN `link_client_and_contract_energy` as cace
  ON a.id = cace.client_id
LEFT JOIN `contract_energy` as ce
  ON cace.contract_id = ce.id
  
LEFT JOIN `invoice_proforma_energy` as iproforma
  ON a.id = iproforma.client_id
LEFT JOIN `invoice_proforma_correction_energy` as iproformacorrection
  ON a.id = iproformacorrection.client_id
LEFT JOIN `invoice_settlement` as isettlement
  ON a.id = isettlement.client_id
LEFT JOIN `invoice_settlement_correction_energy` as isettlementcorrection
  ON a.id = isettlementcorrection.client_id
  
WHERE
  (a.next_payment_request_period IS NULL OR a.next_payment_request_period <= ' . $nowQueryFormat . ')
  AND
  (
      (cg.status_department_id = ' . $fromDepartment->getId() . ' AND (cg.status_contract_finances_id IN (' . implode(",", $containStatusContractIds) . ') ' . $statusContractAdditionalQueryPartGas . '))
      OR
      (ce.status_department_id = ' . $fromDepartment->getId() . ' AND (ce.status_contract_finances_id IN (' . implode(",", $containStatusContractIds) . ') ' . $statusContractAdditionalQueryPartEnergy . '))
  )
  AND
  (
      (cacg.contract_id IS NOT NULL AND cacg.client_id IS NOT NULL)
      OR
      (cace.contract_id IS NOT NULL AND cace.client_id IS NOT NULL)
  )
  AND
  (
      (iproforma.is_paid = 0 AND iproforma.summary_gross_value > 0 AND iproforma.date_of_payment < ' . $nowQueryFormat . ') OR 
      (iproformacorrection.is_paid = 0 AND iproformacorrection.summary_gross_value > 0 AND iproformacorrection.date_of_payment < ' . $nowQueryFormat . ') OR 
      (isettlement.is_paid = 0 AND isettlement.summary_gross_value > 0 AND isettlement.date_of_payment < ' . $nowQueryFormat . ') OR 
      (isettlementcorrection.is_paid = 0 AND isettlementcorrection.summary_gross_value > 0 AND isettlementcorrection.date_of_payment < ' . $nowQueryFormat . ') 
  )
GROUP BY a.id
';

        if ($limit && is_numeric($limit)) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute();

        // returns clients with overdure payment document records
        // those records include all documents active and inactive
        $result = $stmt->fetchAll();
        if (!$result) {
            return null;
        }

        // grab client ids
        $clientsIds = [];
        foreach ($result as $record) {
            $clientsIds[] = $record['id'];
        }

        // get clients by ids
        $clients = $this->em->getRepository('GCRMCRMBundle:Client')->findBy(['id' => $clientsIds]);


        // filter clients that have to pay more than X PLN
        if ($clients) {
            $tmpClients = [];
            foreach ($clients as $client) {
                $documentsStructure = $this->init($client)->generate()->getStructure();

                if ($documentsStructure['balance']['toPay'] > 5) {
                    $tmpClients[] = $client;
                }
            }
            $clients = $tmpClients;
        }


        // get data
        return $this->filterClientsWithOverduePayment($clients, null);
    }

    /**
     * Get proforma and proforma correction documents between billing period date from - to for settlement.
     *
     * @param \DateTime $billingPeriodFrom
     * @param \DateTime $billingPeriodTo
     * @param $isFirstSettlement
     * @param $isLastSettlement
     * @return array - list of proforma and proforma corrections documents
     *                 if proforma have a correction then correction is replaced with proforma
     */
    public function getDocumentsContainedInStatement(\DateTime $billingPeriodFrom, \DateTime $billingPeriodTo, $isFirstSettlement, $isLastSettlement)
    {
        $structure = $this->getStructure();

        $documentsContainedInStatement = [];

        // starts proforma
        $proformaFromBillingPeriodFrom = null;
        // ends proformas
        $proformaFromBillingPeriodTo = null;

        foreach ($structure['data']['invoiceProforma']['records'] as $documentProforma) {
            /** @var \DateTime $statementFromDate */
            $statementFromDate = clone $billingPeriodFrom;
            $statementFromDateFirstDayOfMonth = ((clone $statementFromDate)->modify('first day of this month'))->setTime(0, 0);
            /** @var \DateTime $statementToDate */
            $statementToDate = clone $billingPeriodTo;
            $statementToDateLastDayOfMonth = (clone $statementToDate)->modify('last day of this month')->setTime(0, 0);
            /** @var \DateTime $proformaBillingPeriodFrom */
            $proformaBillingPeriodFrom = clone $documentProforma->getBillingPeriodFrom();
            $proformaBillingPeriodFromFirstDayOfMonth = ((clone $proformaBillingPeriodFrom)->modify('first day of this month'))->setTime(0, 0);
            if (
                $proformaBillingPeriodFromFirstDayOfMonth >= $statementFromDateFirstDayOfMonth &&
                $proformaBillingPeriodFromFirstDayOfMonth < $statementToDateLastDayOfMonth
            ) {
                // backward functionality
                // to properly save proformas to documents

//                // check
//                $checkDateTo = (new \DateTime())->setDate(2020, 8, 1)->setTime(0, 0);
//                if (
//                    ($documentProforma->getCreatedAt())->setTime(0, 0) < $checkDateTo
//                ) {
//                    if (
//                        $documentProforma->getBillingPeriodTo()->format('m') == $billingPeriodTo->format('m') &&
//                        $documentProforma->getBillingPeriodTo()->format('Y') == $billingPeriodTo->format('Y') &&
//                        $billingPeriodTo->format('d') == 1
//                    ) {
//                        continue;
//                    }
//                }
//
//
//                // check
//                $checkDateFrom = (new \DateTime())->setDate(2020, 8, 1)->setTime(0, 0);
//                $checkDateTo = (new \DateTime())->setDate(2020, 9, 18)->setTime(0, 0);
//
//                if (
//                    ($documentProforma->getCreatedAt())->setTime(0, 0) >= $checkDateFrom &&
//                    ($documentProforma->getCreatedAt())->setTime(0, 0) < $checkDateTo
//                ) {
//                    if (
//                        $documentProforma->getBillingPeriodTo()->format('m') == $billingPeriodTo->format('m') &&
//                        $documentProforma->getBillingPeriodTo()->format('Y') == $billingPeriodTo->format('Y') &&
//                        $billingPeriodTo->format('d') < 10
//                    ) {
//                        continue;
//                    }
//                }


//                // check
//                $checkDateFrom = (new \DateTime())->setDate(2020, 9, 18)->setTime(0, 0);
//                if (
//                    ($documentProforma->getCreatedAt())->setTime(0, 0) >= $checkDateFrom
//                ) {
                    // check if first proforma
                    // must match billing period from of settlement - year and months
                    if (
                        $proformaBillingPeriodFromFirstDayOfMonth->format('Y') == $billingPeriodFrom->format('Y') &&
                        $proformaBillingPeriodFromFirstDayOfMonth->format('m') == $billingPeriodFrom->format('m')
                    ) {
                        // first proforma
                        // omit if not first settlement and date from > 10
                        if (!$isFirstSettlement && $billingPeriodFrom->format('d') > 10) {
                            continue;
                        }
                    }

                    // check if last proforma
                    // must match billing period from of settlement - year and months
                    if (
                        $proformaBillingPeriodFromFirstDayOfMonth->format('Y') == $billingPeriodTo->format('Y') &&
                        $proformaBillingPeriodFromFirstDayOfMonth->format('m') == $billingPeriodTo->format('m')
                    ) {
                        // last proforma
                        // omit if not last settlement and date to < 10
                        if (!$isLastSettlement && $billingPeriodTo->format('d') < 10) {
                            continue;
                        }
                    }
//                }

                $mostActiveCorrection = $this->getMostActiveCorrectionFromDocument($documentProforma);
                if ($mostActiveCorrection) {
                    $documentToAdd = $mostActiveCorrection;
                } else {
                    $documentToAdd = $documentProforma;
                }

                if (!$this->isIncludedInSettlements($documentToAdd)) {
                    $documentsContainedInStatement[] = $documentToAdd;
                }
            }
        }

        return $documentsContainedInStatement;
    }

    public function getActiveProformaTypeDocuments()
    {
        $structure = $this->getStructure();

        $result = [];

        /** @var BillingDocumentInterface $documentProforma */
        foreach ($structure['data']['invoiceProforma']['records'] as $documentProforma) {
            if (!$documentProforma->getIsNotActual()) {
                $result[] = $documentProforma;
            }
        }

        /** @var BillingDocumentInterface $documentProforma */
        foreach ($structure['data']['invoiceProformaCorrection']['records'] as $documentProformaCorrection) {
            if (!$documentProformaCorrection->getIsNotActual()) {
                $result[] = $documentProformaCorrection;
            }
        }

        return $result;
    }

    public function getOverpaidValue()
    {
        $structure = $this->getStructure();
        $paidValue = $structure['balance']['paid'];
        $totalDocumentsGrossValue = $structure['balance']['total'];

        if (
            $paidValue == $totalDocumentsGrossValue ||
            $paidValue < $totalDocumentsGrossValue
        ) {
            return 0;
        } else {
            return $paidValue - $totalDocumentsGrossValue;
        }
    }

    public function getPaidValueFromDocuments($documents)
    {
        $paidValue = 0;

        if (!$documents) {
            return 0;
        }

        /** @var BillingDocumentInterface $document */
        foreach ($documents as $document) {
            $paidValue += $document->getPaidValue();
        }

        return $paidValue;
    }

    public function calculateConsumptionFromDocuments(&$documents)
    {
        $consumption = 0;

        if (!$documents) {
            return $consumption;
        }

        foreach ($documents as $document) {
            $consumption += $document->getConsumption();
        }

        return $consumption;
    }
}