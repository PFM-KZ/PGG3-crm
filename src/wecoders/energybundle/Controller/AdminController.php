<?php

namespace Wecoders\EnergyBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\ClientAndContractInterface;
use GCRM\CRMBundle\Entity\Company;
use GCRM\CRMBundle\Entity\ContractEnergyAndDistributionTariff;
use GCRM\CRMBundle\Entity\ContractEnergyAndPriceList;
use GCRM\CRMBundle\Entity\ContractEnergyAndSellerTariff;
use GCRM\CRMBundle\Entity\ContractGasAndDistributionTariff;
use GCRM\CRMBundle\Entity\ContractGasAndPriceList;
use GCRM\CRMBundle\Entity\ContractGasAndSellerTariff;
use GCRM\CRMBundle\Service\AccountNumberIdentifierModel;
use GCRM\CRMBundle\Service\AccountNumberMaker;
use GCRM\CRMBundle\Service\AccountNumberModel;
use Symfony\Component\Form\FormError;
use GCRM\CRMBundle\Entity\ContractGas;
use GCRM\CRMBundle\Service\ClientModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use GCRM\CRMBundle\Service\CompanyModel;
use GCRM\CRMBundle\Service\ModulesModel;
use Wecoders\EnergyBundle\Entity\BillingDocumentInterface;
use Wecoders\EnergyBundle\Entity\DebitNote;
use Wecoders\EnergyBundle\Entity\DebitNotePackage;
use Wecoders\EnergyBundle\Entity\DebitNotePackageRecord;
use Wecoders\EnergyBundle\Entity\InvoiceCollective;
use Wecoders\EnergyBundle\Entity\Tariff;
use GCRM\CRMBundle\Entity\ContractEnergy;
use GCRM\CRMBundle\Entity\StatusContract;
use GCRM\CRMBundle\Service\ContractModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use GCRM\CRMBundle\Service\EasyAdminModel;
use GCRM\CRMBundle\Entity\StatusDepartment;
use Wecoders\EnergyBundle\Entity\PriceList;
use GCRM\CRMBundle\Entity\ContractInterface;
use GCRM\CRMBundle\Entity\ContractEnergyBase;
use Symfony\Component\HttpFoundation\Request;
use Wecoders\EnergyBundle\Entity\InvoiceBase;
use Symfony\Component\HttpFoundation\Response;
use Wecoders\EnergyBundle\Event\BillingRecordGeneratedEvent;
use Wecoders\EnergyBundle\Form\FileUploadType;
use Wecoders\EnergyBundle\Service\DebitNoteModel;
use Wecoders\EnergyBundle\Service\DebitNotePackageModel;
use Wecoders\EnergyBundle\Service\DebitNotePackageRecordModel;
use Wecoders\EnergyBundle\Service\EnveloModel;
use GCRM\CRMBundle\Entity\ClientAndContractGas;
use GCRM\CRMBundle\Entity\StatusContractAction;
use GCRM\CRMBundle\Service\StatusContractModel;
use Wecoders\EnergyBundle\Service\InvoiceModel;
use Wecoders\EnergyBundle\Service\SpreadsheetReader;
use Wecoders\EnergyBundle\WecodersEnergyBundle;
use Wecoders\InvoiceBundle\Service\InvoiceData;
use Wecoders\InvoiceBundle\Service\NumberModel;
use Wecoders\EnergyBundle\Entity\PaymentRequest;
use GCRM\CRMBundle\Service\StatusDepartmentModel;
use Wecoders\EnergyBundle\Entity\InvoiceProforma;
use Wecoders\EnergyBundle\Form\AuthorizationType;
use Wecoders\EnergyBundle\Service\ColonnadeModel;
use GCRM\CRMBundle\Entity\ClientAndContractEnergy;
use Wecoders\EnergyBundle\Entity\InvoiceInterface;
use Wecoders\EnergyBundle\Service\SettlementModel;
use Wecoders\InvoiceBundle\Entity\InvoiceTemplate;
use Wecoders\EnergyBundle\Entity\InvoiceSettlement;
use Wecoders\EnergyBundle\Entity\PackageToGenerate;
use Wecoders\EnergyBundle\Entity\SettlementPackage;
use Wecoders\EnergyBundle\Service\ContractAccessor;
use GCRM\CRMBundle\Entity\StatusContractAuthorization;
use Wecoders\EnergyBundle\Form\SettlementFromFileType;
use Wecoders\EnergyBundle\Service\PaymentRequestModel;
use GCRM\CRMBundle\Service\BillingDocument\Initializer;
use Wecoders\InvoiceBundle\Entity\InvoiceNumberSettings;
use Wecoders\InvoiceBundle\Service\InvoiceTemplateModel;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Wecoders\EnergyBundle\Entity\ContractEnergyInterface;
use Wecoders\EnergyBundle\Entity\SettlementPackageRecord;
use Wecoders\EnergyBundle\Service\PackageToGenerateModel;
use Wecoders\EnergyBundle\Service\SettlementPackageModel;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Wecoders\EnergyBundle\Entity\ContractAccessorInterface;
use Wecoders\EnergyBundle\Entity\InvoiceEstimatedSettlement;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Wecoders\EnergyBundle\Service\SettlementPackageRecordModel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Wecoders\EnergyBundle\Entity\PaymentRequestPackageToGenerate;
use Wecoders\EnergyBundle\Service\PaymentRequestPackageToGenerateModel;


class AdminController extends Controller
{
    const OVERDUE_PAYMENT_IDS_PARAM = 'overdue_payment_ids';
    const OVERDUE_PAYMENT_IDS_COMPLETED_PARAM = 'overdue_payments_ids_completed';
    const OVERDUE_PAYMENT_WITH_CC_IDS_PARAM = 'overdue_payment_with_cc_ids';
    const OVERDUE_PAYMENT_WITH_CC_IDS_COMPLETED_PARAM = 'overdue_payments_with_cc_ids_completed';

    /**
     * @Route("/admin/get-file-data-clients-with-overdue-payments", name="getFileDataClientsWithOverduePayments")
     */
    public function getFileDataClientsWithOverduePaymentsAction(Request $request, EntityManager $em, \GCRM\CRMBundle\Service\InvoiceModel $invoiceModel, Initializer $initializer)
    {
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        /** @var SessionInterface $session */
        $session = $request->getSession();

        $clientIds = $session->get(self::OVERDUE_PAYMENT_IDS_PARAM);
        $unsetAll = false;

        if(!$clientIds) { // process first half
            $qb = $em->createQueryBuilder()->select('c.id')->from('GCRM\CRMBundle\Entity\Client', 'c');
            $clientData = $qb->getQuery()->getResult();
            $result = array();
            foreach($clientData as $data) {
                $result[] = $data['id'];
            }
            $clientIds = $result;
            $session->set(self::OVERDUE_PAYMENT_IDS_PARAM, json_encode($clientIds));
            // get first half
            $clientIds = array_splice($clientIds, 0, floor(count($clientIds)/2));
        } else { // process second half
            $idsCompleted = $session->get(self::OVERDUE_PAYMENT_IDS_COMPLETED_PARAM);
            $allIds = json_decode($clientIds);
            if($idsCompleted) { // set clientIds to the array_diff
                $idsCompleted = json_decode($idsCompleted);
                $clientIds = array_values(array_diff($allIds, $idsCompleted));
                $unsetAll = true;
            } else { // first batch didnt get processed successfully
                $clientIds = array_splice($allIds, 0, floor(count($allIds)/2));
            }
        }

        $qb = $em->createQueryBuilder();

        $clients = $qb
            ->select('c')
            ->from('GCRM\CRMBundle\Entity\Client', 'c')
            ->where($qb->expr()->in('c.id', $clientIds))
            ->getQuery()
            ->getResult()
        ;

        $spreadsheet = new Spreadsheet();

        $spreadsheet->getActiveSheet()->setCellValue('A1', 'Lp.');
        $spreadsheet->getActiveSheet()->setCellValue('B1', 'Imię');
        $spreadsheet->getActiveSheet()->setCellValue('C1', 'Nazwisko');
        $spreadsheet->getActiveSheet()->setCellValue('D1', 'Telefon');
        $spreadsheet->getActiveSheet()->setCellValue('E1', 'Faktura numer');
        $spreadsheet->getActiveSheet()->setCellValue('F1', 'Okres rozliczeniowy');
        $spreadsheet->getActiveSheet()->setCellValue('G1', 'Okres rozliczeniowy od');
        $spreadsheet->getActiveSheet()->setCellValue('H1', 'Okres rozliczeniowy do');
        $spreadsheet->getActiveSheet()->setCellValue('I1', 'Dni po terminie');
        $spreadsheet->getActiveSheet()->setCellValue('J1', 'Kwota na fakturze');
        $spreadsheet->getActiveSheet()->setCellValue('K1', 'Opłacono');
        $spreadsheet->getActiveSheet()->setCellValue('L1', 'Kwota do zapłaty');
        $spreadsheet->getActiveSheet()->setCellValue('M1', 'PESEL');
        $spreadsheet->getActiveSheet()->setCellValue('N1', 'NIP');
        $spreadsheet->getActiveSheet()->setCellValue('O1', 'Indywidualny nr rachunku');
        $spreadsheet->getActiveSheet()->setCellValue('P1', 'Typ dokumentu');
        $spreadsheet->getActiveSheet()->setCellValue('Q1', 'Aktualny status');
        $spreadsheet->getActiveSheet()->setCellValue('R1', 'Kod PPE');
        $spreadsheet->getActiveSheet()->setCellValue('S1', 'Dystrybutor');
        $spreadsheet->getActiveSheet()->setCellValue('T1', 'Dystrybutor oddział');
        $spreadsheet->getActiveSheet()->setCellValue('U1', 'Numer umowy');
        $spreadsheet->getActiveSheet()->setCellValue('V1', 'Data uruchomienia usługi');
        $spreadsheet->getActiveSheet()->setCellValue('W1', 'Termin płatności');

        /** @var Client $client */
        $index = 2;
        foreach ($clients as $client) {
            /** @var ContractEnergyBase $contract */
            $contract = $this->getClientContract($client);

            $documentsStructure = $initializer->init($client)->generate()->getStructure();
            foreach ($documentsStructure['data'] as $data) {
                /** @var BillingDocumentInterface $document */
                foreach ($data['records'] as $document) {
                    if ($document->getIsNotActual()) { // gets only actual documents
                        continue;
                    }

                    // gets overdue date of payment
                    /** @var \DateTime $dateStart */
                    $dateStart = $document->getDateOfPayment();
                    $dateStart = $dateStart->setTime(0,0);
                    $dateEnd = new \DateTime('now');

                    if ($dateStart < $dateEnd) {
                        $diff = $dateStart->diff($dateEnd);
                        $diffDays = $diff->days;
                    } else {
                        $diffDays = 0;
                    }

                    if (!$document->getIsPaid() && $diffDays > 0) {
                        $spreadsheet->getActiveSheet()->setCellValue('A' . $index, $index - 1);
                        $spreadsheet->getActiveSheet()->setCellValue('B' . $index, $client->getName());
                        $spreadsheet->getActiveSheet()->setCellValue('C' . $index, $client->getSurname());
                        $spreadsheet->getActiveSheet()->setCellValue('D' . $index, $client->getTelephoneNr());
                        $spreadsheet->getActiveSheet()->setCellValue('E' . $index, $document->getNumber());
                        $spreadsheet->getActiveSheet()->setCellValue('F' . $index, method_exists($document, 'getBillingPeriod') ? $document->getBillingPeriod() : null);
                        /** @var \DateTime $billingPeriodFrom */
                        $billingPeriodFrom = method_exists($document, 'getBillingPeriodFrom') ? $document->getBillingPeriodFrom() : null;
                        $billingPeriodFrom = $billingPeriodFrom ? $billingPeriodFrom->format('d-m-Y') : null;
                        $spreadsheet->getActiveSheet()->setCellValue('G' . $index, $billingPeriodFrom);
                        /** @var \DateTime $billingPeriodTo */
                        $billingPeriodTo = method_exists($document, 'getBillingPeriodTo') ? $document->getBillingPeriodTo() : null;
                        $billingPeriodTo = $billingPeriodTo ? $billingPeriodTo->format('d-m-Y') : null;
                        $spreadsheet->getActiveSheet()->setCellValue('H' . $index, $billingPeriodTo);
                        $spreadsheet->getActiveSheet()->setCellValue('I' . $index, $diffDays);
                        $spreadsheet->getActiveSheet()->setCellValue('J' . $index, $document->getSummaryGrossValue());
                        $spreadsheet->getActiveSheet()->setCellValue('K' . $index, $document->getPaidValue());
                        $spreadsheet->getActiveSheet()->setCellValue('L' . $index, $document->getSummaryGrossValue() - $document->getPaidValue());
                        $spreadsheet->getActiveSheet()->setCellValue('M' . $index, $client->getPesel());
                        $spreadsheet->getActiveSheet()->setCellValue('N' . $index, $client->getNip());
                        $spreadsheet->getActiveSheet()->setCellValue('O' . $index, $client->getAccountNumberIdentifier()->getNumber());
                        $spreadsheet->getActiveSheet()->setCellValue('P' . $index, $data['label']);
                        $spreadsheet->getActiveSheet()->setCellValue('Q' . $index, ($contract ? ($contract->getStatusContractFinances() ? $contract->getStatusContractFinances() : $contract->getStatusContractProcess()) : null));
                        $spreadsheet->getActiveSheet()->setCellValue('R' . $index, ($contract ? $contract->getPpCodeByDate(new \DateTime()) : null));
                        $spreadsheet->getActiveSheet()->setCellValue('S' . $index, ($contract ? $contract->getDistributor() : null));
                        $spreadsheet->getActiveSheet()->setCellValue('T' . $index, ($contract ? $contract->getDistributorBranch() : null));
                        $spreadsheet->getActiveSheet()->setCellValue('U' . $index, ($contract ? $contract->getContractNumber() : null));
                        $spreadsheet->getActiveSheet()->setCellValue('V' . $index, ($contract ? ($contract->getContractFromDate() ? $contract->getContractFromDate()->format('d-m-Y') : null) : null));
                        $spreadsheet->getActiveSheet()->setCellValue('W' . $index, ($document->getDateOfPayment() ? $document->getDateOfPayment()->format('d-m-Y') : null));

                        $index++;
                    }
                }
            }
        }

        if($unsetAll) {
            $session->remove(self::OVERDUE_PAYMENT_IDS_PARAM);
            $session->remove(self::OVERDUE_PAYMENT_IDS_COMPLETED_PARAM);
        } else {
            $session->set(self::OVERDUE_PAYMENT_IDS_COMPLETED_PARAM, json_encode($clientIds));
        }

        if ($spreadsheet) {
            $this->downloadSpreadsheetAsXlsx($spreadsheet);
        } else {
            $this->addFlash('notice', 'Brak rekordów');
        }
    }

    /**
     * @Route("/admin/get-file-data-clients-with-overdue-payments-for-cc", name="getFileDataClientsWithOverduePaymentsForCc")
     */
    public function getFileDataClientsWithOverduePaymentsForCcAction(Request $request, EntityManager $em, \GCRM\CRMBundle\Service\InvoiceModel $invoiceModel, Initializer $initializer)
    {
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        /** @var SessionInterface $session */
        $session = $request->getSession();

        $clientIds = $session->get(self::OVERDUE_PAYMENT_WITH_CC_IDS_PARAM);
        $unsetAll = false;

        if(!$clientIds) { // process first half
            $qb = $em->createQueryBuilder()->select('c.id')->from('GCRM\CRMBundle\Entity\Client', 'c');
            $clientData = $qb->getQuery()->getResult();
            $result = array();
            foreach($clientData as $data) {
                $result[] = $data['id'];
            }
            $clientIds = $result;
            $session->set(self::OVERDUE_PAYMENT_WITH_CC_IDS_PARAM, json_encode($clientIds));
            // get first half
            $clientIds = array_splice($clientIds, 0, floor(count($clientIds)/2));
        } else { // process second half
            $idsCompleted = $session->get(self::OVERDUE_PAYMENT_WITH_CC_IDS_COMPLETED_PARAM);
            $allIds = json_decode($clientIds);
            if($idsCompleted) { // set clientIds to the array_diff
                $idsCompleted = json_decode($idsCompleted);
                $clientIds = array_values(array_diff($allIds, $idsCompleted));
                $unsetAll = true;
            } else { // first batch didnt get processed successfully
                $clientIds = array_splice($allIds, 0, floor(count($allIds)/2));
            }
        }

        $qb = $em->createQueryBuilder();

        $clients = $qb
            ->select('c')
            ->from('GCRM\CRMBundle\Entity\Client', 'c')
            ->where($qb->expr()->in('c.id', $clientIds))
            ->getQuery()
            ->getResult()
        ;

        $spreadsheet = new Spreadsheet();

        $spreadsheet->getActiveSheet()->setCellValue('A1', 'Imie');
        $spreadsheet->getActiveSheet()->setCellValue('B1', 'Nazwisko');
        $spreadsheet->getActiveSheet()->setCellValue('C1', 'Telefon');
        $spreadsheet->getActiveSheet()->setCellValue('D1', 'Faktura_numer');
        $spreadsheet->getActiveSheet()->setCellValue('E1', 'Okres_rozliczeniowy');
        $spreadsheet->getActiveSheet()->setCellValue('F1', 'Po_terminie');
        $spreadsheet->getActiveSheet()->setCellValue('G1', 'Kwota_do_zaplaty');
        $spreadsheet->getActiveSheet()->setCellValue('H1', 'PESEL');
        $spreadsheet->getActiveSheet()->setCellValue('I1', 'NIP');
        $spreadsheet->getActiveSheet()->setCellValue('J1', 'Indywidualny_nr_rachunku');
        $spreadsheet->getActiveSheet()->setCellValue('K1', 'Ilosc_faktur');
        $spreadsheet->getActiveSheet()->setCellValue('L1', 'Typ_dokumentu');
        $spreadsheet->getActiveSheet()->setCellValue('M1', 'Aktualny_status');
        $spreadsheet->getActiveSheet()->setCellValue('N1', 'Kod_PPE');
        $spreadsheet->getActiveSheet()->setCellValue('O1', 'Dystrybutor');
        $spreadsheet->getActiveSheet()->setCellValue('P1', 'Dystrybutor_oddzial');
        $spreadsheet->getActiveSheet()->setCellValue('Q1', 'Data_kiedy_oplaci_1_kontakt');
        $spreadsheet->getActiveSheet()->setCellValue('R1', 'Czy_oplacil_zaleglosc_2_kontakt');
        $spreadsheet->getActiveSheet()->setCellValue('S1', 'Uwagi_1_kontakt');
        $spreadsheet->getActiveSheet()->setCellValue('T1', 'Uwagi_2_kontakt');
        $spreadsheet->getActiveSheet()->setCellValue('U1', 'Uwagi_ogolne_do_calosci');
        $spreadsheet->getActiveSheet()->setCellValue('V1', 'Numer umowy');
        $spreadsheet->getActiveSheet()->setCellValue('W1', 'Data uruchomienia usługi');

        /** @var Client $client */
        $index = 2;
        foreach ($clients as $client) {
            /** @var ContractEnergyBase $contract */
            $contract = $this->getClientContract($client);

            $documentsStructure = $initializer->init($client)->generate()->getStructure();
            $documents = [];
            foreach ($documentsStructure['data'] as $data) {

                /** @var BillingDocumentInterface $document */
                foreach ($data['records'] as $document) {
                    if ($document->getIsNotActual()) { // gets only actual documents
                        continue;
                    }

                    // gets overdue date of payment
                    /** @var \DateTime $dateStart */
                    $dateStart = $document->getDateOfPayment();
                    $dateStart = $dateStart->setTime(0,0);
                    $dateEnd = new \DateTime('now');

                    if ($dateStart < $dateEnd) {
                        $diff = $dateStart->diff($dateEnd);
                        $diffDays = $diff->days;
                    } else {
                        $diffDays = 0;
                    }

                    if (!$document->getIsPaid() && $diffDays > 0) {
                        // apply diff days and label to increase speed
                        $document->diffDays = $diffDays;
                        $document->typeLabel = $data['label'];

                        $documents[] = $document;
                    }
                }
            }

            if (count($documents)) {
                $invoiceNumbers = [];
                $billingPeriods = [];
                $diffDays = [];
                $toPay = 0;
                $documentTypes = [];
                foreach ($documents as $item) {
                    $invoiceNumbers[] = $item->getNumber();

                    /** @var \DateTime $billingPeriodFrom */
                    $billingPeriodFrom = method_exists($item, 'getBillingPeriodFrom') ? $item->getBillingPeriodFrom() : null;
                    $billingPeriodFrom = $billingPeriodFrom ? $billingPeriodFrom->format('d-m-Y') : null;
                    /** @var \DateTime $billingPeriodTo */
                    $billingPeriodTo = method_exists($item, 'getBillingPeriodTo') ? $item->getBillingPeriodTo() : null;
                    $billingPeriodTo = $billingPeriodTo ? $billingPeriodTo->format('d-m-Y') : null;
                    if ($billingPeriodFrom && $billingPeriodTo) {
                        $billingPeriods[] = $billingPeriodFrom . '-' . $billingPeriodTo;
                    }

                    $diffDays[] = $item->diffDays;

                    $toPay += (number_format($item->getSummaryGrossValue() - $item->getPaidValue(), 2, '.', ''));

                    $documentTypes[] = $item->typeLabel;
                }

                $spreadsheet->getActiveSheet()->setCellValue('A' . $index, $client->getName());
                $spreadsheet->getActiveSheet()->setCellValue('B' . $index, $client->getSurname());
                $spreadsheet->getActiveSheet()->setCellValue('C' . $index, $client->getTelephoneNr());
                $spreadsheet->getActiveSheet()->setCellValue('D' . $index, implode(', ', $invoiceNumbers));
                $spreadsheet->getActiveSheet()->setCellValue('E' . $index, implode(', ', $billingPeriods));
                $spreadsheet->getActiveSheet()->setCellValue('F' . $index, implode(', ', $diffDays));
                $spreadsheet->getActiveSheet()->setCellValue('G' . $index, $toPay);
                $spreadsheet->getActiveSheet()->setCellValue('H' . $index, $client->getPesel());
                $spreadsheet->getActiveSheet()->setCellValue('I' . $index, $client->getNip());
                $spreadsheet->getActiveSheet()->setCellValue('J' . $index, $client->getAccountNumberIdentifier()->getNumber());
                $spreadsheet->getActiveSheet()->setCellValue('K' . $index, count($documents));
                $spreadsheet->getActiveSheet()->setCellValue('L' . $index, implode(', ', $documentTypes));
                $spreadsheet->getActiveSheet()->setCellValue('M' . $index, ($contract ? ($contract->getStatusContractFinances() ? $contract->getStatusContractFinances() : $contract->getStatusContractProcess()) : null));
                $spreadsheet->getActiveSheet()->setCellValue('N' . $index, ($contract ? $contract->getPpCodeByDate(new \DateTime()) : null));
                $spreadsheet->getActiveSheet()->setCellValue('O' . $index, ($contract ? $contract->getDistributor() : null));
                $spreadsheet->getActiveSheet()->setCellValue('P' . $index, ($contract ? $contract->getDistributorBranch() : null));
                $spreadsheet->getActiveSheet()->setCellValue('Q' . $index, null);
                $spreadsheet->getActiveSheet()->setCellValue('R' . $index, null);
                $spreadsheet->getActiveSheet()->setCellValue('S' . $index, null);
                $spreadsheet->getActiveSheet()->setCellValue('T' . $index, null);
                $spreadsheet->getActiveSheet()->setCellValue('U' . $index, null);
                $spreadsheet->getActiveSheet()->setCellValue('V' . $index, ($contract ? $contract->getContractNumber() : null));
                $spreadsheet->getActiveSheet()->setCellValue('W' . $index, ($contract ? ($contract->getContractFromDate() ? $contract->getContractFromDate()->format('d-m-Y') : null) : null));

                $index++;
            }
        }

        if($unsetAll) {
            $session->remove(self::OVERDUE_PAYMENT_WITH_CC_IDS_PARAM);
            $session->remove(self::OVERDUE_PAYMENT_WITH_CC_IDS_COMPLETED_PARAM);
        } else {
            $session->set(self::OVERDUE_PAYMENT_WITH_CC_IDS_COMPLETED_PARAM, json_encode($clientIds));
        }

        if ($spreadsheet) {
            $this->downloadSpreadsheetAsXlsx($spreadsheet);
        } else {
            $this->addFlash('notice', 'Brak rekordów');
        }
    }

    private function getClientContract(Client $client)
    {
        $contract = null;
        /** @var ClientAndContractEnergy $clientAndContract */
        foreach ($client->getClientAndEnergyContracts() as $clientAndContract) {
            $contract = $clientAndContract->getContract();
            if ($contract) {
                break;
            }
        }

        if (!$contract) {
            /** @var ClientAndContractGas $clientAndContract */
            foreach ($client->getClientAndGasContracts() as $clientAndContract) {
                $contract = $clientAndContract->getContract();
                if ($contract) {
                    break;
                }
            }
        }

        return $contract;
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

    /**
     * @Route("/actual-to-invoice", name="actualToInvoice")
     */
    public function actualToInvoiceAction(Request $request, EntityManager $em, StatusDepartmentModel $statusDepartmentModel, StatusContractModel $statusContractModel)
    {
        /** @var StatusDepartment $statusDepartmentFinances */
        $statusDepartmentFinances = $statusDepartmentModel->getRecordByCode(StatusDepartmentModel::DEPARTMENT_FINANCES_CODE);
        if (!$statusDepartmentFinances) {
            die('Brak status departamentu finansowego.');
        }

        $daysToCheckForward = $em->getRepository('GCRMCRMBundle:Settings')->findOneBy(['name' => 'actual_to_invoice_days_to_check_forward']);
        if ($daysToCheckForward === null) {
            $daysToCheckForward = 7;
        } else {
            $daysToCheckForward = (int) $daysToCheckForward->getContent();
        }

        // gets list of packages to generate
        $packages = $em->getRepository('WecodersEnergyBundle:PackageToGenerate')->findAll();


        $ids = [];
        $statusContracts = $statusContractModel->getStatusContractsBySpecialActionOption(StatusContractModel::SPECIAL_ACTION_CHOOSE_TO_PROFORMA);
        /** @var StatusContract $statusContract */
        foreach ($statusContracts as $statusContract) {
            $ids[] = $statusContract->getId();
        }

        $contractsGas = $this->getContractsRecordsReadyToGenerateDocuments($em, 'link_client_and_contract_gas', 'contract_gas', 'GCRMCRMBundle:ContractGas', $statusDepartmentFinances->getId(), $daysToCheckForward, $ids);
        $contractsEnergy = $this->getContractsRecordsReadyToGenerateDocuments($em, 'link_client_and_contract_energy', 'contract_energy', 'GCRMCRMBundle:ContractEnergy', $statusDepartmentFinances->getId(), $daysToCheckForward, $ids);
        if (
            $request->query->has('download-gas') && count($contractsGas) ||
            $request->query->has('download-energy') && count($contractsEnergy)
        ) {
            $contracts = $request->query->has('download-gas') ? $contractsGas : $contractsEnergy;
            $spreadsheet = new Spreadsheet();
            $spreadsheet->getActiveSheet()->setCellValue('A1', 'Lp.');
            $spreadsheet->getActiveSheet()->setCellValue('B1', 'Indywidualny nr rachunku');

            $index = 2;
            foreach ($contracts as $contract) {
                $spreadsheet->getActiveSheet()->setCellValue('A' . $index, $index - 1);
                $spreadsheet->getActiveSheet()->setCellValue('B' . $index, $contract['badge_id']);

                $index++;
            }

            $this->downloadSpreadsheetAsXlsx($spreadsheet);
        }

        if ($request->query->has('package-download-data-id') && $request->query->get('package-download-data-id')) {
            $packageId = $request->query->get('package-download-data-id');
            /** @var PackageToGenerate $package */
            $package = $em->getRepository('WecodersEnergyBundle:PackageToGenerate')->find($packageId);
            $documentIds = $package->getDocumentIds();

            $invoices = $em->getRepository('WecodersEnergyBundle:InvoiceProforma')->findBy(['id' => $documentIds]);

            $spreadsheet = new Spreadsheet();
            $spreadsheet->getActiveSheet()->setCellValue('A1', 'Lp.');
            $spreadsheet->getActiveSheet()->setCellValue('B1', 'Indywidualny nr rachunku');
            $spreadsheet->getActiveSheet()->setCellValue('C1', 'Nr dokumentu');

            $index = 2;
            /** @var InvoiceBase $invoice */
            foreach ($invoices as $invoice) {
                /** @var Client $client */
                $client = $invoice->getClient();
                if (!$client) {
                    die('Dokument nr: ' . $invoice->getNumber() . ' nie ma przypisanego klienta');
                }
                $badgeId = $client->getAccountNumberIdentifier()->getNumber();
                if (!$badgeId) {
                    die('Klient #' . $client->getId() . ' ' . $client->getName() . ' '  . $client->getSurname() . ' nie ma przypisanego indywidualnego numeru rachunku');
                }

                $spreadsheet->getActiveSheet()->setCellValue('A' . $index, $index - 1);
                $spreadsheet->getActiveSheet()->setCellValue('B' . $index, $badgeId);
                $spreadsheet->getActiveSheet()->setCellValue('C' . $index, $invoice->getNumber());

                $index++;
            }

            $this->downloadSpreadsheetAsXlsx($spreadsheet);
        }

        return $this->render('@GCRMCRM/Default/actual-to-invoice.html.twig', [
            'contractsGas' => $contractsGas,
            'contractsEnergy' => $contractsEnergy,
            'packages' => $packages,
            'daysToCheckForward' => $daysToCheckForward,
            'statusContracts' => $statusContracts,
        ]);
    }

    /**
     * @Route("/actual-to-settlement", name="actualToSettlement")
     */
    public function actualToSettlementAction(Request $request, EntityManager $em, ContainerInterface $container, ContractAccessor $contractAccessor)
    {
        $form = $this->createForm(SettlementFromFileType::class);
        $form->handleRequest($request);

        // INPUT FILE RECORDS
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            $createdDate = $form->get('createdDate')->getData();

            $kernelRootDir = $container->get('kernel')->getRootDir();
            $tmpFilename = 'tmp-settlements-from-file';
            $absoluteUploadDirectoryPath = $kernelRootDir . '/../var/data';
            $fullPathToFile = $kernelRootDir . '/../var/data/' . $tmpFilename;
            if (file_exists($fullPathToFile)) {
                unlink($fullPathToFile);
            }

            $file->move($absoluteUploadDirectoryPath, $tmpFilename);

            if (file_exists($fullPathToFile)) {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
                $rows = $this->getDataRowsUniversal($reader, $fullPathToFile, 2, 'C');


                $notFound = [];
                foreach ($rows as $row) {
                    $pp = $row[0];
                    $clientAndContract = $contractAccessor->accessClientAndContractBy('ppCode', $pp, 'contractAndPpCode');
                    if (!$clientAndContract) {
                        $notFound[] = $pp;
                    }
                }
                if (count($notFound)) {
                    return new Response('Plik nie został wgrany ponieważ nie znaleziono klientów/umów dla danych kodów PP (popraw dane z pliku i wgraj jeszcze raz):<br>' . implode('<br>', $notFound));
                }

                $em->getConnection()->beginTransaction();
                try {
                    $settlementPackage = new SettlementPackage();
                    $settlementPackage->setStatus(SettlementPackageModel::STATUS_WAITING_TO_PROCESS);
                    $settlementPackage->setAddedBy($this->getUser());
                    if ($createdDate) {
                        $settlementPackage->setCreatedDate($createdDate);
                    }
                    $em->persist($settlementPackage);
                    $em->flush($settlementPackage);

                    foreach ($rows as $row) {

                        $pp = $row[0];
                        /** @var ClientAndContractInterface $clientAndContract */
                        $clientAndContract = $contractAccessor->accessClientAndContractBy('ppCode', $pp, 'contractAndPpCode');
                        $client = $clientAndContract->getClient();

                        $dateFrom = $row[1] ? (\DateTime::createFromFormat('d_m_Y', $row[1]))->setTime(0, 0) : null;
                        $dateTo = $row[2] ? (\DateTime::createFromFormat('d_m_Y', $row[2]))->setTime(0, 0) : null;

                        $settlementPackageRecord = new SettlementPackageRecord();
                        $settlementPackageRecord->setSettlementPackage($settlementPackage);
                        $settlementPackageRecord->setStatus(SettlementPackageModel::STATUS_WAITING_TO_PROCESS);
                        $settlementPackageRecord->setPp($pp);
                        $settlementPackageRecord->setDateFrom($dateFrom);
                        $settlementPackageRecord->setDateTo($dateTo);
                        $settlementPackageRecord->setClient($client);

                        $em->persist($settlementPackageRecord);
                        $em->flush();
                    }

                    $em->getConnection()->commit();
                } catch (\Exception $e) {
                    $em->getConnection()->rollBack();
                }

                $this->addFlash('success', 'Wygenerowano rekordy.');
                unlink($fullPathToFile);
                return $this->redirectToRoute('actualToSettlement');
            }
        }


        $packages = $em->getRepository('WecodersEnergyBundle:SettlementPackage')->findAll();


        return $this->render('@WecodersEnergyBundle/default/actual-to-settlement.html.twig', [
            'formInputFile' => $form->createView(),
            'packages' => $packages,
        ]);
    }

    /**
     * @Route("/actual-to-debit-note", name="actualToDebitNote")
     */
    public function actualToDebitNoteAction(
        Request $request,
        EntityManager $em,
        ContainerInterface $container,
        ContractAccessor $contractAccessor,
        SpreadsheetReader $spreadsheetReader,
        InvoiceTemplateModel $invoiceTemplateModel,
        Initializer $initializer,
        StatusContractModel $statusContractModel,
        \Wecoders\EnergyBundle\Service\ContractModel $contractModel
    )
    {
        $form = $this->createForm(SettlementFromFileType::class);
        $form->handleRequest($request);

        // INPUT FILE RECORDS
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            $createdDate = $form->get('createdDate')->getData();

            $kernelRootDir = $container->get('kernel')->getRootDir();
            $tmpFilename = 'tmp-debit-notes-from-file';
            $absoluteUploadDirectoryPath = $kernelRootDir . '/../var/data';
            $fullPathToFile = $kernelRootDir . '/../var/data/' . $tmpFilename;
            if (file_exists($fullPathToFile)) {
                unlink($fullPathToFile);
            }

            $file->move($absoluteUploadDirectoryPath, $tmpFilename);

            if (file_exists($fullPathToFile)) {
                $rows = $spreadsheetReader->fetchRows('Xlsx', $fullPathToFile, 2, 'C');

                $notFound = [];
                foreach ($rows as $row) {
                    $accountNumberIdentifier = $row[0];
                    $clientAndContract = $contractAccessor->accessClientAndContractBy('number', $accountNumberIdentifier, 'accountNumberIdentifier');
                    if (!$clientAndContract) {
                        $notFound[] = $accountNumberIdentifier;
                    }
                }
                if (count($notFound)) {
                    return new Response('Plik nie został wgrany ponieważ nie znaleziono klientów/umów dla danych ID rachunku (popraw dane z pliku i wgraj jeszcze raz):<br>' . implode('<br>', $notFound));
                }

                $accountNumberIdentifiers = [];
                foreach ($rows as $row) {
                    $accountNumberIdentifiers[] = $row[0];
                }
                $this->processDebitNote(
                    $em,
                    $contractAccessor,
                    $invoiceTemplateModel,
                    $statusContractModel,
                    $contractModel,
                    $createdDate,
                    $accountNumberIdentifiers
                );

                unlink($fullPathToFile);
                return $this->redirectToRoute('actualToDebitNote');
            }
        }




        if ($request->request->get('multiPackageRequestMakePackage')) {
            $selectedRows = $request->get('selectedRows');

            if ($selectedRows) {
                $selectedClientIds = [];
                $selectedClients = [];
                $accountNumberIdentifiers = [];
                foreach ($selectedRows as $rowId) {
                    /** @var Client $row */
                    $row = $this->getDoctrine()->getRepository('GCRMCRMBundle:Client')->find($rowId);
                    if ($row) {
                        $selectedClientIds[] = $rowId;
                        $selectedClients[] = $row;
                        $accountNumberIdentifiers[] = $row->getAccountNumberIdentifier()->getNumber();
                    }
                }

                $multiMakePackageChooseDate = $request->request->get('multiMakePackageChooseDate', null);
                if ($multiMakePackageChooseDate) {
                    $createDate = \DateTime::createFromFormat('Y-m-d', $multiMakePackageChooseDate);
                    $createDate->setTime(0,0,0);
                } else {
                    $createDate = new \DateTime();
                    $createDate->setTime(0,0,0);
                }

                $this->processDebitNote(
                    $em,
                    $contractAccessor,
                    $invoiceTemplateModel,
                    $statusContractModel,
                    $contractModel,
                    $createDate,
                    $accountNumberIdentifiers
                );
                return $this->redirectToRoute('actualToDebitNote');
            }
        }



        $packages = $em->getRepository(DebitNotePackage::class)->findAll();

        $data = $initializer->getClientsToDebitNotes(
            $statusContractModel->getStatusContractsIdsBySpecialActionOption([
                StatusContractModel::SPECIAL_ACTION_CHOOSE_TO_DEBIT_NOTE_RESIGNATION_BEFORE_PROCESS,
                StatusContractModel::SPECIAL_ACTION_CHOOSE_TO_DEBIT_NOTE_RESIGNATION_AFTER_PROCESS,
            ]),
            100
        );

        return $this->render('@WecodersEnergyBundle/default/actual-to-debit-note.html.twig', [
            'formInputFile' => $form->createView(),
            'packages' => $packages,
            'data' => $data
        ]);
    }

    private function processDebitNote(
        EntityManagerInterface $em,
        ContractAccessor $contractAccessor,
        InvoiceTemplateModel $invoiceTemplateModel,
        StatusContractModel $statusContractModel,
        \Wecoders\EnergyBundle\Service\ContractModel $contractModel,
        $createdDate,
        $records
    )
    {
        $em->getConnection()->beginTransaction();
        try {
            $package = new DebitNotePackage();
            $package->setStatus(DebitNotePackageModel::STATUS_WAITING_TO_PROCESS);
            $package->setAddedBy($this->getUser());
            if ($createdDate) {
                $package->setCreatedDate($createdDate);
            }
            $em->persist($package);

            foreach ($records as $accountNumberIdentifier) {
                /** @var ClientAndContractInterface $clientAndContract */
                $clientAndContract = $contractAccessor->accessClientAndContractBy('number', $accountNumberIdentifier, 'accountNumberIdentifier');
                /** @var ContractEnergyBase $contract */
                $contract = $clientAndContract->getContract();
                /** @var Client $client */
                $client = $clientAndContract->getClient();

                $packageRecord = new DebitNotePackageRecord();
                $packageRecord->setPackage($package);
                $packageRecord->setStatus(DebitNotePackageModel::STATUS_WAITING_TO_PROCESS);
                $packageRecord->setAccountNumberIdentifier($client->getAccountNumberIdentifier()->getNumber());
                $packageRecord->setClient($client);
                $packageRecord->setBrand($contract->getBrand());
                // contract data
                $packageRecord->setContractType($contract->getType());
                $packageRecord->setContractNumber($contract->getContractNumber());
                $packageRecord->setContractFromDate($contract->getContractFromDate());
                $packageRecord->setContractToDate($contract->getContractToDate());
                $packageRecord->setContractSignDate($contract->getSignDate());
                $packageRecord->setContractSignDate($contract->getSignDate());
                $packageRecord->setPenaltyAmountPerMonth($contract->getPenaltyAmountPerMonth());
                // calculated data
                $packageRecord->setMonthsNumber($contractModel->calculateMonths($contract));
                $packageRecord->setSummaryGrossValue($packageRecord->getMonthsNumber() * $packageRecord->getPenaltyAmountPerMonth());

                // choose template
                if ($statusContractModel->containSpecialActionOption($contract->getActualStatus(), StatusContractModel::SPECIAL_ACTION_CHOOSE_TO_DEBIT_NOTE_RESIGNATION_BEFORE_PROCESS)) {
                    $documentTemplate = $invoiceTemplateModel->getTemplateRecordByCode('debit_note_' . strtolower($contract->getType()) . '_before');
                } elseif ($statusContractModel->containSpecialActionOption($contract->getActualStatus(), StatusContractModel::SPECIAL_ACTION_CHOOSE_TO_DEBIT_NOTE_RESIGNATION_AFTER_PROCESS)) {
                    $documentTemplate = $invoiceTemplateModel->getTemplateRecordByCode('debit_note_' . strtolower($contract->getType()) . '_after');
                } else {
                    throw new \RuntimeException('Special action mismatch error.');
                }

                if (!$documentTemplate) {
                    throw new \RuntimeException('Debit note document template not found.');
                }

                $packageRecord->setDocumentTemplate($documentTemplate);

                $em->persist($packageRecord);
                $em->flush();
            }

            $em->getConnection()->commit();
            $this->addFlash('success', 'Wygenerowano rekordy.');
        } catch (\Exception $e) {
            $em->getConnection()->rollBack();
            $this->addFlash('error', 'Wystąpiły błędy, spróbuj ponownie. Komunikat błędu: ' . $e->getMessage());
        }
    }

    /**
     * @Route("/manage-last-billing-period-to", name="manageLastBillingPeriodTo")
     */
    public function manageLastBillingPeriodToAction(
        Request $request,
        ContainerInterface $container,
        SpreadsheetReader $spreadsheetReader,
        ClientModel $clientModel,
        Initializer $initializer
    )
    {
        $form = $this->createForm(FileUploadType::class);
        $form->handleRequest($request);

        // INPUT FILE RECORDS
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();

            $kernelRootDir = $container->get('kernel')->getRootDir();
            $tmpFilename = 'tmp-last-billing-period-to-from-file';
            $absoluteUploadDirectoryPath = $kernelRootDir . '/../var/data';
            $fullPathToFile = $kernelRootDir . '/../var/data/' . $tmpFilename;
            if (file_exists($fullPathToFile)) {
                unlink($fullPathToFile);
            }

            $file->move($absoluteUploadDirectoryPath, $tmpFilename);

            if (file_exists($fullPathToFile)) {
                $rows = $spreadsheetReader->fetchRows('Xlsx', $fullPathToFile, 2, 'B');

                foreach ($rows as $key => $row) {
                    $accountNumberIdentifier = $rows[$key][0];
                    $client = $clientModel->getClientByBadgeId($accountNumberIdentifier);
                    if (!$client) {
                        $rows[$key][1] = 'Nie znaleziono klienta';
                        continue;
                    }

                    $object = $initializer->init($client)->generate();
                    $mostActiveSettlementDocument = $object->getMostActiveSettlementDocument();
                    if (!$mostActiveSettlementDocument) {
                        $rows[$key][1] = 'Brak rozliczenia';
                        continue;
                    }

                    $billingPeriodTo = $mostActiveSettlementDocument->getBillingPeriodTo();
                    if (!$billingPeriodTo) {
                        $rows[$key][1] = 'Błędny format daty';
                        continue;
                    }

                    $rows[$key][1] = $billingPeriodTo->format('Y-m-d');
                }


                // build spreadsheet
                $spreadsheet = new Spreadsheet();

                $spreadsheet->getActiveSheet()->setCellValue('A1', 'Nr rach.');
                $spreadsheet->getActiveSheet()->setCellValue('B1', 'Data do');

                $col = 1;
                $index = 2;

                foreach ($rows as $row) {
                    $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow($col++, $index, $row[0]);
                    $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow($col, $index, $row[1]);
                    $index++;
                    $col = 1;
                }

                unlink($fullPathToFile);
                $this->downloadSpreadsheetAsXlsx($spreadsheet);
            }
        }

        return $this->render('@WecodersEnergyBundle/default/manage-last-billing-period-to.html.twig', [
            'formInputFile' => $form->createView(),
        ]);
    }

    /**
     * @Route("/actual-to-settlement/{id}", name="actualToSettlementPackage")
     */
    public function actualToSettlementPackageAction(Request $request, EntityManager $em, EasyAdminModel $easyAdminModel, Initializer $initializer, \Wecoders\InvoiceBundle\Service\InvoiceModel $invoiceBundleInvoiceModel, $id)
    {
        $settlementPackage = $em->getRepository('WecodersEnergyBundle:SettlementPackage')->find($id);
        if (!$settlementPackage) {
            throw new NotFoundHttpException();
        }

        $settlementPackageRecords = $em->getRepository('WecodersEnergyBundle:SettlementPackageRecord')->findBy([
            'settlementPackage' => $settlementPackage
        ]);





        // ACTIONS

        // ============
        if ($request->request->get('multiClick')) {
            $selectedRows = $request->get('selectedRows');

            if ($selectedRows) {
                $selectedPackageRecords = [];
                foreach ($selectedRows as $rowId) {
                    $row = $this->getDoctrine()->getRepository('WecodersEnergyBundle:SettlementPackageRecord')->find($rowId);
                    if ($row) {
                        $selectedPackageRecords[] = $row;
                    }
                }

                if (count($selectedPackageRecords)) {
                    /** @var SettlementPackageRecord $settlementPackageRecord */
                    foreach ($selectedPackageRecords as $settlementPackageRecord) {
                        if (
                            $settlementPackageRecord->getStatus() == SettlementPackageRecordModel::STATUS_WAITING_TO_PROCESS ||
                            $settlementPackageRecord->getStatus() == SettlementPackageRecordModel::STATUS_PROCESS_ERROR
                        ) {
                            $settlementPackageRecord->setStatus(SettlementPackageRecordModel::STATUS_IN_PROCESS);
                            $settlementPackageRecord->setErrorMessage(null);
                            $em->persist($settlementPackageRecord);
                            $em->flush($settlementPackageRecord);
                        } elseif (
                            $settlementPackageRecord->getStatus() == SettlementPackageRecordModel::STATUS_WAITING_TO_GENERATE ||
                            $settlementPackageRecord->getStatus() == SettlementPackageRecordModel::STATUS_GENERATE_ERROR
                        ) {
                            $settlementPackageRecord->setStatus(SettlementPackageRecordModel::STATUS_GENERATE);
                            $settlementPackageRecord->setErrorMessage(null);
                            $em->persist($settlementPackageRecord);
                            $em->flush($settlementPackageRecord);
                        }
                    }

                    $this->addFlash('success', 'Wykonano');
                    return $this->redirectToRoute('actualToSettlementPackage', ['id' => $id]);
                }
            }
        }

        $changeStatusToProcess = $request->request->get('changeStatusToProcessAction');
        if ($changeStatusToProcess) {
            /** @var SettlementPackageRecord $settlementPackageRecord */
            $settlementPackageRecord = $this->getDoctrine()->getRepository('WecodersEnergyBundle:SettlementPackageRecord')->find($changeStatusToProcess);
            if ($settlementPackageRecord) {
                $settlementPackageRecord->setStatus(SettlementPackageRecordModel::STATUS_IN_PROCESS);
                $settlementPackageRecord->setErrorMessage(null);
                $em->persist($settlementPackageRecord);
                $em->flush();
            }

            return $this->redirectToRoute('actualToSettlementPackage', ['id' => $id]);
        }

        $changeStatusToProcess = $request->request->get('changeStatusToGenerateAction');
        if ($changeStatusToProcess) {
            /** @var SettlementPackageRecord $settlementPackageRecord */
            $settlementPackageRecord = $this->getDoctrine()->getRepository('WecodersEnergyBundle:SettlementPackageRecord')->find($changeStatusToProcess);
            if ($settlementPackageRecord) {
                $settlementPackageRecord->setStatus(SettlementPackageRecordModel::STATUS_GENERATE);
                $settlementPackageRecord->setErrorMessage(null);
                $em->persist($settlementPackageRecord);
                $em->flush();
            }

            return $this->redirectToRoute('actualToSettlementPackage', ['id' => $id]);
        }


        $multiRollBack = $request->request->get('multiRollBack');
        if ($multiRollBack) {
            $kernelRootDir = $this->container->get('kernel')->getRootDir();
            $selectedRows = $request->get('selectedRows');
            if ($selectedRows) {
                foreach ($selectedRows as $rowId) {
                    /** @var SettlementPackageRecord $settlementPackageRecord */
                    $settlementPackageRecord = $this->getDoctrine()->getRepository('WecodersEnergyBundle:SettlementPackageRecord')->find($rowId);
                    if ($settlementPackageRecord) {
                        $em->getConnection()->beginTransaction();
                        try {
                            $documentToRemove = null;

                            // remove generated documents records
                            $document = $settlementPackageRecord->getInvoiceSettlement();
                            if ($document && $document instanceof InvoiceSettlement) {
                                $documentToRemove = $document;
                            }

                            $document = $settlementPackageRecord->getInvoiceEstimatedSettlement();
                            if ($document && $document instanceof InvoiceEstimatedSettlement) {
                                $documentToRemove = $document;
                            }

                            // remove package
                            $settlementPackageRecord = $this->getDoctrine()->getRepository('WecodersEnergyBundle:SettlementPackageRecord')->find($settlementPackageRecord->getId());
                            $em->remove($settlementPackageRecord);
                            $em->flush();


                            $em->getConnection()->commit();

                            if ($settlementPackageRecord->getClient()) {
                                $billingDocumentsObject = $initializer->init($settlementPackageRecord->getClient())->generate();
                                $billingDocumentsObject->updateDocumentsIsPaidState();
                            }

                            if ($documentToRemove) {
                                if ($documentToRemove instanceof InvoiceSettlement) {
                                    $directoryRelative = $easyAdminModel->getEntityDirectoryRelativeByEntityName('InvoiceSettlementEnergy');
                                } else {
                                    $directoryRelative = $easyAdminModel->getEntityDirectoryRelativeByEntityName('InvoiceEstimatedSettlementEnergy');
                                }

                                $invoicePath = $invoiceBundleInvoiceModel->fullInvoicePath($kernelRootDir, $documentToRemove, $directoryRelative);
                                if (file_exists($invoicePath . '.pdf')) {
                                    unlink($invoicePath . '.pdf');
                                }
                                if (file_exists($invoicePath . '.docx')) {
                                    unlink($invoicePath . '.docx');
                                }
                            }

                        } catch (\Exception $e) {
                            $em->getConnection()->rollBack();
                        }
                    }
                }

                $this->addFlash('success', 'Wykonano');
                return $this->redirectToRoute('actualToSettlementPackage', ['id' => $id]);
            }
        }

        return $this->render('@WecodersEnergyBundle/default/actual-to-settlement-package.html.twig', [
            'package' => $settlementPackage,
            'records' => $settlementPackageRecords,
        ]);
    }

    /**
     * @Route("/actual-to-debit-note/{id}", name="actualToDebitNotePackage")
     */
    public function actualToDebitNotePackageAction(
        Request $request,
        EntityManager $em,
        EasyAdminModel $easyAdminModel,
        Initializer $initializer,
        DebitNoteModel $debitNoteModel,
        DebitNotePackageModel $debitNotePackageModel,
        DebitNotePackageRecordModel $debitNotePackageRecordModel,
        $id
    )
    {
        /** @var DebitNotePackage $package */
        $package = $debitNotePackageModel->getRecord($id);
        if (!$package) {
            throw new NotFoundHttpException();
        }

        $packageRecords = $package->getPackageRecords();


        // ACTIONS
        // ============
        if ($request->request->get('multiClick')) {
            $selectedRows = $request->get('selectedRows');
            if ($selectedRows) {
                $packageRecords = [];
                foreach ($selectedRows as $rowId) {
                    $row = $debitNotePackageRecordModel->getRecord($rowId);
                    if ($row) {
                        $packageRecords[] = $row;
                    }
                }

                if (count($packageRecords)) {
                    /** @var DebitNotePackageRecord $packageRecord */
                    foreach ($packageRecords as $packageRecord) {
                        if (
                            $packageRecord->getStatus() == DebitNotePackageRecordModel::STATUS_WAITING_TO_PROCESS ||
                            $packageRecord->getStatus() == DebitNotePackageRecordModel::STATUS_PROCESS_ERROR
                        ) {
                            $packageRecord->setStatus(DebitNotePackageRecordModel::STATUS_IN_PROCESS);
                            $packageRecord->setErrorMessage(null);
                            $em->persist($packageRecord);
                            $em->flush($packageRecord);
                        } elseif (
                            $packageRecord->getStatus() == DebitNotePackageRecordModel::STATUS_WAITING_TO_GENERATE ||
                            $packageRecord->getStatus() == DebitNotePackageRecordModel::STATUS_GENERATE_ERROR
                        ) {
                            $packageRecord->setStatus(DebitNotePackageRecordModel::STATUS_GENERATE);
                            $packageRecord->setErrorMessage(null);
                            $em->persist($packageRecord);
                            $em->flush($packageRecord);
                        }
                    }

                    $this->addFlash('success', 'Wykonano');
                    return $this->redirectToRoute('actualToDebitNotePackage', ['id' => $id]);
                }
            }
        }

        $changeStatusToProcess = $request->request->get('changeStatusToProcessAction');
        if ($changeStatusToProcess) {
            /** @var DebitNotePackageRecord $packageRecord */
            $packageRecord = $debitNotePackageRecordModel->getRecord($changeStatusToProcess);
            if ($packageRecord) {
                $packageRecord->setStatus(DebitNotePackageRecordModel::STATUS_IN_PROCESS);
                $packageRecord->setErrorMessage(null);
                $em->persist($packageRecord);
                $em->flush();
            }

            return $this->redirectToRoute('actualToDebitNotePackage', ['id' => $id]);
        }

        $changeStatusToProcess = $request->request->get('changeStatusToGenerateAction');
        if ($changeStatusToProcess) {
            /** @var DebitNotePackageRecord $packageRecord */
            $packageRecord = $debitNotePackageRecordModel->getRecord($changeStatusToProcess);
            if ($packageRecord) {
                $packageRecord->setStatus(DebitNotePackageRecordModel::STATUS_GENERATE);
                $packageRecord->setErrorMessage(null);
                $em->persist($packageRecord);
                $em->flush();
            }

            return $this->redirectToRoute('actualToDebitNotePackage', ['id' => $id]);
        }


        $multiRollBack = $request->request->get('multiRollBack');
        if ($multiRollBack) {
            $kernelRootDir = $this->container->get('kernel')->getRootDir();
            $selectedRows = $request->get('selectedRows');
            if ($selectedRows) {
                foreach ($selectedRows as $rowId) {
                    /** @var SettlementPackageRecord $settlementPackageRecord */
                    $packageRecord = $debitNotePackageRecordModel->getRecord($rowId);
                    if ($packageRecord) {
                        $em->getConnection()->beginTransaction();
                        try {
                            $documentToRemove = null;

                            // remove generated documents records
                            $document = $packageRecord->getDocument();
                            if ($document) {
                                $documentToRemove = $document;
                            }

                            // remove package
                            $packageRecord = $debitNotePackageRecordModel->getRecord($packageRecord->getId());
                            $em->remove($packageRecord);
                            $em->flush();

                            $em->getConnection()->commit();

                            if ($packageRecord->getClient()) {
                                $billingDocumentsObject = $initializer->init($packageRecord->getClient())->generate();
                                $billingDocumentsObject->updateDocumentsIsPaidState();
                            }

                            if ($documentToRemove) {
                                $documentPath = $debitNoteModel->getDocumentPath($documentToRemove, $easyAdminModel->getEntityDirectoryByEntityName('DebitNote'));
                                if (file_exists($documentPath . '.pdf')) {
                                    unlink($documentPath . '.pdf');
                                }
                                if (file_exists($documentPath . '.docx')) {
                                    unlink($documentPath . '.docx');
                                }
                            }

                        } catch (\Exception $e) {
                            $em->getConnection()->rollBack();
                        }
                    }
                }

                $this->addFlash('success', 'Wykonano');
                return $this->redirectToRoute('actualToDebitNotePackage', ['id' => $id]);
            }
        }

        return $this->render('@WecodersEnergyBundle/default/actual-to-debit-note-package.html.twig', [
            'package' => $package,
            'records' => $packageRecords,
        ]);
    }


    protected function getDataRowsUniversal($reader, $file, $firstDataRowIndex, $highestColumn)
    {
        try {
            $spreadsheet = $reader->load($file);
        } catch (\Exception $e) {
            die('File format error: check if you choosen correct file and try again.');
        }
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

    /**
     * @Route("/actual-to-payment-request", name="actualToPaymentRequest")
     */
    public function actualToPaymentRequestAction(Request $request, EntityManager $em, StatusDepartmentModel $statusDepartmentModel, Initializer $initializer, ClientModel $clientModel, StatusContractModel $statusContractModel, PaymentRequestPackageToGenerateModel $paymentRequestPackageToGenerateModel, EnveloModel $enveloModel)
    {
        /** @var StatusDepartment $statusDepartmentFinances */
        $statusDepartmentFinances = $statusDepartmentModel->getRecordByCode(StatusDepartmentModel::DEPARTMENT_FINANCES_CODE);
        if (!$statusDepartmentFinances) {
            die('Brak status departamentu finansowego.');
        }

        $data = $initializer->getClientsWithOverduePayment(
            $statusDepartmentFinances,
            $statusContractModel->getStatusContractsIdsBySpecialActionOption(StatusContractModel::SPECIAL_ACTION_CHOOSE_TO_PAYMENT_REQUEST),
            true,
            null
        );


        if ($request->request->get('multiPackageRequestMakePackage')) {
            $selectedRows = $request->get('selectedRows');

            if ($selectedRows) {
                $selectedClientIds = [];
                $selectedClients = [];
                foreach ($selectedRows as $rowId) {
                    $row = $this->getDoctrine()->getRepository('GCRMCRMBundle:Client')->find($rowId);
                    if ($row) {
                        $selectedClientIds[] = $rowId;
                        $selectedClients[] = $row;
                    }
                }

                if ($selectedClientIds) {
                    $em->getConnection()->beginTransaction();
                    try {
                        $multiMakePackageChooseDate = $request->request->get('multiMakePackageChooseDate', null);
                        if ($multiMakePackageChooseDate) {
                            $createDate = \DateTime::createFromFormat('Y-m-d', $multiMakePackageChooseDate);
                            $createDate->setTime(0,0,0);
                        } else {
                            $createDate = new \DateTime();
                            $createDate->setTime(0,0,0);
                        }

                        $package = new PaymentRequestPackageToGenerate();
                        $package->setObjectIds($selectedClientIds);
                        $package->setAddedBy($this->getUser());
                        $package->setStatus(PaymentRequestPackageToGenerateModel::STATUS_WAITING_TO_PROCESS);
                        $package->setCreatedDate($createDate);

                        $em->persist($package);
                        $em->flush();


                        /** @var Client $selectedClient */
                        foreach ($selectedClients as $selectedClient) {
                            $beforeTmp = null;
                            $nextTmp = null;
                            if ($selectedClient->getNextPaymentRequestPeriod()) {
                                $beforeTmp = $selectedClient->getNextPaymentRequestPeriod();
                            }

                            $nextTmp = new \DateTime();
                            $nextTmp->modify('+' . 14 . ' days');

                            $selectedClient->setBeforePaymentRequestPeriod($beforeTmp);
                            $selectedClient->setNextPaymentRequestPeriod($nextTmp);

                            $em->persist($selectedClient);
                            $em->flush();
                        }

                        $em->getConnection()->commit();

                        $this->addFlash('success', 'Paczka utworzona.');
                    } catch (\Exception $e) {
                        $em->getConnection()->rollBack();
                    }

                    return $this->redirectToRoute('actualToPaymentRequest');
                }
            }
        }







        if ($request->request->get('multiRollBack')) {
            $selectedRows = $request->get('selectedRows');

            if ($selectedRows) {
                foreach ($selectedRows as $rowId) {
                    /** @var PaymentRequestPackageToGenerate $packageToGenerate */
                    $packageToGenerate = $this->getDoctrine()->getRepository('WecodersEnergyBundle:PaymentRequestPackageToGenerate')->find($rowId);
                    if ($packageToGenerate) {
                        $selectedClients = $this->getDoctrine()->getRepository('GCRMCRMBundle:Client')->findBy(['id' => $packageToGenerate->getObjectIds()]);
                        $documents = $this->getDoctrine()->getRepository('WecodersEnergyBundle:PaymentRequest')->findBy(['id' => $packageToGenerate->getDocumentIds()]);

                        $em->getConnection()->beginTransaction();
                        try {
                            // bring back clients payment request periods to before state
                            /** @var Client $selectedClient */
                            foreach ($selectedClients as $selectedClient) {
                                // adds before invoice period and next
                                $selectedClient->setNextPaymentRequestPeriod($selectedClient->getBeforePaymentRequestPeriod());
                                $selectedClient->setBeforePaymentRequestPeriod(null);
                                $em->persist($selectedClient);
                                $em->flush();
                            }

                            // remove generated documents records
                            if ($documents && count($documents)) {
                                foreach ($documents as $document) {

                                    // Dispatching the event
                                    $billingRecordGeneratedEvent = new BillingRecordGeneratedEvent($document);
                                    $this->container->get('event_dispatcher')->dispatch('billing_record.removed', $billingRecordGeneratedEvent);

                                    $em->remove($document);
                                    $em->flush();
                                }
                            }

                            $em->remove($packageToGenerate);
                            $em->flush();

                            $em->getConnection()->commit();
                        } catch (\Exception $e) {
                            $em->getConnection()->rollBack();
                        }
                    }
                }
            }
        }







        $changeStatusToProcessAction = $request->request->get('changeStatusToProcessAction');
        $changeStatusToGenerateAction = $request->request->get('changeStatusToGenerateAction');

        if ($changeStatusToProcessAction) {
            $packageToGenerate = $paymentRequestPackageToGenerateModel->getRecord($changeStatusToProcessAction);
            if ($packageToGenerate) {
                $packageToGenerate->setErrorMessage(null);
                $packageToGenerate->setStatus(PackageToGenerateModel::STATUS_IN_PROCESS);
                $em->persist($packageToGenerate);
                $em->flush();
            }
            return $this->redirectToRoute('actualToPaymentRequest');
        } elseif ($changeStatusToGenerateAction) {
            $packageToGenerate = $paymentRequestPackageToGenerateModel->getRecord($changeStatusToGenerateAction);
            if ($packageToGenerate) {
                $packageToGenerate->setErrorMessage(null);
                $packageToGenerate->setStatus(PackageToGenerateModel::STATUS_GENERATE);
                $em->persist($packageToGenerate);
                $em->flush();
            }
            return $this->redirectToRoute('actualToPaymentRequest');
        }





        $generateEnveloAction = $request->request->get('generateEnveloAction');
        if ($generateEnveloAction) {
            /** @var PaymentRequestPackageToGenerate $packageToGenerate */
            $packageToGenerate = $this->getDoctrine()->getRepository('WecodersEnergyBundle:PaymentRequestPackageToGenerate')->find($generateEnveloAction);
            if ($packageToGenerate) {
                $documents = $this->getDoctrine()->getRepository('WecodersEnergyBundle:PaymentRequest')->findBy(['id' => $packageToGenerate->getDocumentIds()]);
                $enveloModel->generateForPaymentRequest($packageToGenerate->getId(), $documents);
            }
        }




        // gets list of packages to generate
        $packages = $em->getRepository('WecodersEnergyBundle:PaymentRequestPackageToGenerate')->findAll();

        return $this->render('@GCRMCRM/Default/actual-to-payment-request.html.twig', [
            'data' => $data,
            'packages' => $packages,
        ]);
    }

    private function getContractsRecordsReadyToGenerateDocuments(EntityManager $em, $clientAndContractsEntity, $contractsEntity, $contractEntityFullName, $fromDepartamentId, $daysToCheckForward, $statusContractIds)
    {
        $conn = $em->getConnection();
        $now = new \DateTime();
        $now->modify('+' . $daysToCheckForward . ' days');

        $financesSqlParts = [];
        $financesSqlParts[] = 'c.status_contract_finances_id IS NULL';
        foreach ($statusContractIds as $id) {
            $financesSqlParts[] = 'c.status_contract_finances_id = ' . $id;
        }
        $financesSqlMerged = '(' . implode(' OR ', $financesSqlParts) . ')';

        $sql = '
SELECT 
  a.id, a.name, a.surname, a.pesel, a.telephone_nr, a.account_number_identifier_id,
  c.id as contract_id, c.contract_number, c.contract_from_date, c.next_invoicing_period, c.is_marked_to_generate_invoice, c.is_invoice_generated, c.tariff_id,
  ani.number as badge_id
FROM `client` a
LEFT JOIN `' . $clientAndContractsEntity . '` as lc
  ON a.id = lc.client_id
LEFT JOIN `' . $contractsEntity . '` as c
  ON lc.contract_id = c.id
LEFT JOIN `account_number_identifier` as ani
  ON a.account_number_identifier_id = ani.id
WHERE 
  c.id IS NOT NULL 
  AND c.status_department_id = ' . $fromDepartamentId . '
  AND '. $financesSqlMerged .'
  AND c.is_resignation != 1
  AND c.is_broken_contract != 1
  AND
  (
    (c.next_invoicing_period IS NOT NULL AND c.next_invoicing_period <= "' . $now->format('Y-m-d') . ' 00:00:00") 
    OR 
    (c.next_invoicing_period IS NULL AND c.contract_from_date IS NOT NULL AND c.contract_from_date <= "' . $now->format('Y-m-d') . ' 00:00:00")
  )
';

        // AND c.status_contract_finances_id IS NULL
        // AND (c.status_contract_finances_id IS NULL OR c.status_contract_finances_id = 41 OR c.status_contract_finances_id = 40 OR c.status_contract_finances_id = 44 OR c.status_contract_finances_id = 2)

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();

        // apply tariff code
        if ($result) {
            foreach ($result as &$contractData) {
                $tmpContract = $em->getRepository($contractEntityFullName)->find($contractData['contract_id']);
                $tariffByDate = $tmpContract->getSellerTariffByDate(new \DateTime());
                $contractData['tariff_code'] = $tariffByDate ? $tariffByDate->getCode() : '';
            }
        }

        return $result;
    }

    /**
     * @Route("/contractsToGenerateListPostAction", name="contractsToGenerateListPostAction")
     */
    public function contractsToGenerateListPostAction(Request $request, EntityManager $em)
    {
        $entityClass = $request->query->get('entityClass');
        $contractType = $request->query->get('contractType');
        $selectedRows = $request->get('selectedRows');

        $selectedContractsIds = [];

        if ($selectedRows) {
            $multiMakePackage = $request->request->get('multiMakePackage', null);
            $multiMakePackageChooseDate = $request->request->get('multiMakePackageChooseDate', null);
            if ($multiMakePackageChooseDate) {
                $createDate = \DateTime::createFromFormat('Y-m-d', $multiMakePackageChooseDate);
                $createDate->setTime(0,0,0);
            } else {
                $createDate = new \DateTime();
                $createDate->setTime(0,0,0);
            }

            if ($multiMakePackage) {
                $contracts = [];
                foreach ($selectedRows as $rowId) {
                    $row = $this->getDoctrine()->getRepository('GCRMCRMBundle:' . $entityClass)->find($rowId);
                    if ($row) {
                        $contracts[] = $row;
                        $selectedContractsIds[] = $rowId;
                    }
                }

                if ($selectedContractsIds) {
                    $em->getConnection()->beginTransaction();
                    try {
                        /** @var ContractEnergyBase $contract */
                        foreach ($contracts as $contract) {
                            /** @var Tariff $tariff */
                            $tariff = $contract->getSellerTariffByDate(new \DateTime());
                            if (!$tariff) {
                                die('Umowa nie ma wybranej taryfy: ' . $contract->getContractNumber());
                            }

                            $tariffInvoicingPeriodInMonths = $tariff->getInvoicingPeriodInMonths();
                            if (!$tariffInvoicingPeriodInMonths) {
                                if (($key = array_search($contract->getId(), $selectedContractsIds)) !== false) {
                                    unset($selectedContractsIds[$key]);
                                }
                                continue;
                            }

                            $beforeTmp = null;
                            $nextTmp = null;
                            if ($contract->getNextInvoicingPeriod()) {
                                $beforeTmp = clone $contract->getNextInvoicingPeriod();
                                $nextTmp = clone $contract->getNextInvoicingPeriod();
                                $nextTmp->modify('first day of this month');
                                $nextTmp->modify('+' . $tariffInvoicingPeriodInMonths . ' months');
                                $nextTmp->modify('first day of this month');
                            } elseif ($contract->getContractFromDate()) {
                                $nextTmp = clone $contract->getContractFromDate();
                                $nextTmp->modify('first day of this month');
                                $nextTmp->modify('+' . $tariffInvoicingPeriodInMonths . ' months');
                                $nextTmp->modify('first day of this month');
                            } else {
                                die('Umowa nie ma uzupełnionego pola "okres obowiązywania od": ' . $contract->getContractNumber());
                            }

                            $contract->setBeforeInvoicingPeriod($beforeTmp);
                            $contract->setNextInvoicingPeriod($nextTmp);
                            $em->persist($contract);
                            $em->flush();
                        }

                        if (count($selectedContractsIds)) {
                            $package = new PackageToGenerate();
                            $package->setContractIds($selectedContractsIds);
                            $package->setAddedBy($this->getUser());
                            $package->setContractType($contractType);
                            $package->setStatus(PackageToGenerateModel::STATUS_WAITING_TO_PROCESS);
                            $package->setCreatedDate($createDate);

                            $em->persist($package);
                            $em->flush();

                            $em->getConnection()->commit();

                            $this->addFlash('success', 'Paczka utworzona.');
                        } else {
                            $this->addFlash('warning', 'Nie wybrano prawidłowych umów do paczki.');
                        }
                    } catch (\Exception $e) {
                        $em->getConnection()->rollBack();
                    }


                }
            }
        }

        return $this->redirectToRoute('actualToInvoice');
    }




    /**
     * @Route("/packagesSettlementToGenerateListPostAction", name="packagesSettlementToGenerateListPostAction")
     */
    public function packagesSettlementToGenerateListPostAction(Request $request, EntityManager $em, Initializer $initializer, EnveloModel $enveloModel, InvoiceModel $invoiceModel, EasyAdminModel $easyAdminModel, \Wecoders\InvoiceBundle\Service\InvoiceModel $invoiceBundleInvoiceModel)
    {
        $kernelRootDir = $this->get('kernel')->getRootDir();

        $changeStatusToProcess = $request->request->get('changeStatusToProcessAction');
        if ($changeStatusToProcess) {
            /** @var SettlementPackage $settlementPackage */
            $settlementPackage = $this->getDoctrine()->getRepository('WecodersEnergyBundle:SettlementPackage')->find($changeStatusToProcess);
            if ($settlementPackage) {
//                $settlementPackage->setErrorMessage(null);
                $settlementPackage->setStatus(SettlementPackageModel::STATUS_IN_PROCESS);
                $em->persist($settlementPackage);
                $em->flush();
            }

            return $this->redirectToRoute('actualToSettlement');
        }

        $changeStatusToGenerate = $request->request->get('changeStatusToGenerateAction');
        if ($changeStatusToGenerate) {
            /** @var SettlementPackage $settlementPackage */
            $settlementPackage = $this->getDoctrine()->getRepository('WecodersEnergyBundle:SettlementPackage')->find($changeStatusToGenerate);
            if ($settlementPackage) {
                $settlementPackage->setErrorMessage(null);
                $settlementPackage->setStatus(SettlementPackageModel::STATUS_GENERATE);
                $em->persist($settlementPackage);
                $em->flush();
            }

            return $this->redirectToRoute('actualToSettlement');
        }
//
        $generateEnveloAction = $request->request->get('generateEnveloAction');
        if ($generateEnveloAction) {
            /** @var SettlementPackage $settlementPackage */
            $settlementPackage = $this->getDoctrine()->getRepository('WecodersEnergyBundle:SettlementPackage')->find($generateEnveloAction);
            if ($settlementPackage) {
                $settlementPackageRecords = $settlementPackage->getSettlementPackageRecords();
                $documents = [];
                /** @var SettlementPackageRecord $settlementPackageRecord */
                foreach ($settlementPackageRecords as $settlementPackageRecord) {
                    if ($settlementPackageRecord->getStatus() != SettlementPackageRecordModel::STATUS_COMPLETE) {
                        continue;
                    }

                    if ($settlementPackageRecord->getInvoiceSettlement()) {
                        $documents[] = $settlementPackageRecord->getInvoiceSettlement();
                    } elseif ($settlementPackageRecord->getInvoiceEstimatedSettlement()) {
                        $documents[] = $settlementPackageRecord->getInvoiceEstimatedSettlement();
                    }
                }

                if (count($documents)) {
                    $enveloModel->generateForSettlements($settlementPackage->getId(), $documents);
                }
            }
        }

        $selectedRows = $request->get('selectedRows');

        if ($selectedRows) {
            $multiRollBack = $request->request->get('multiRollBack');

            if ($multiRollBack) {


                foreach ($selectedRows as $rowId) {
                    /** @var SettlementPackage $settlementPackage */
                    $settlementPackage = $this->getDoctrine()->getRepository('WecodersEnergyBundle:SettlementPackage')->find($rowId);
                    if ($settlementPackage) {
                        $packageRecords = $settlementPackage->getSettlementPackageRecords();
                        if ($packageRecords) {
                            $em->getConnection()->beginTransaction();
                            try {
                                $documents = [];
                                $clients = [];
                                /** @var SettlementPackageRecord $packageRecord */
                                foreach ($packageRecords as $packageRecord) {
                                    // remove generated documents records
                                    $document = $packageRecord->getInvoiceSettlement();
                                    if ($document && $document instanceof InvoiceSettlement) {
                                        $documents[] = $document;
                                    }

                                    $document = $packageRecord->getInvoiceEstimatedSettlement();
                                    if ($document && $document instanceof InvoiceEstimatedSettlement) {
                                        $documents[] = $document;
                                    }

                                    $clients[] = $packageRecord->getClient();
                                }

                                if (count($documents)) {
                                    foreach ($documents as $document) {
                                        // Dispatching the event
                                        $billingRecordGeneratedEvent = new BillingRecordGeneratedEvent($document);
                                        $this->container->get('event_dispatcher')->dispatch('billing_record.removed', $billingRecordGeneratedEvent);
                                    }
                                }

                                // remove package
                                $settlementPackage = $this->getDoctrine()->getRepository('WecodersEnergyBundle:SettlementPackage')->find($settlementPackage->getId());
                                $em->remove($settlementPackage);
                                $em->flush();



                                $em->getConnection()->commit();


                                foreach ($clients as $client) {
                                    $billingDocumentsObject = $initializer->init($client)->generate();
                                    $billingDocumentsObject->updateDocumentsIsPaidState();
                                }

                                foreach ($documents as $document) {
                                    if ($document instanceof InvoiceSettlement) {
                                        $directoryRelative = $easyAdminModel->getEntityDirectoryRelativeByEntityName('InvoiceSettlementEnergy');
                                    } else {
                                        $directoryRelative = $easyAdminModel->getEntityDirectoryRelativeByEntityName('InvoiceEstimatedSettlementEnergy');
                                    }
                                    $invoicePath = $invoiceBundleInvoiceModel->fullInvoicePath($kernelRootDir, $document, $directoryRelative);
                                    if (file_exists($invoicePath . '.pdf')) {
                                        unlink($invoicePath . '.pdf');
                                    }
                                    if (file_exists($invoicePath . '.docx')) {
                                        unlink($invoicePath . '.docx');
                                    }
                                }
                            } catch (\Exception $e) {
                                $em->getConnection()->rollBack();
                            }
                        }
                    }
                }
            }
        }

        return $this->redirectToRoute('actualToSettlement');
    }

    /**
     * @Route("/packagesDebitNoteToGenerateListPostAction", name="packagesDebitNoteToGenerateListPostAction")
     */
    public function packagesDebitNoteToGenerateListPostAction(
        Request $request,
        EntityManager $em,
        Initializer $initializer,
        EnveloModel $enveloModel,
        DebitNotePackageModel $debitNotePackageModel,
        DebitNoteModel $debitNoteModel,
        EasyAdminModel $easyAdminModel,
        \Wecoders\InvoiceBundle\Service\InvoiceModel $invoiceBundleInvoiceModel
    )
    {
        $kernelRootDir = $this->get('kernel')->getRootDir();

        $changeStatusToProcess = $request->request->get('changeStatusToProcessAction');
        if ($changeStatusToProcess) {
            /** @var SettlementPackage $package */
            $package = $debitNotePackageModel->getRecord($changeStatusToProcess);
            if ($package) {
//                $settlementPackage->setErrorMessage(null);
                $package->setStatus(SettlementPackageModel::STATUS_IN_PROCESS);
                $em->persist($package);
                $em->flush();
            }

            return $this->redirectToRoute('actualToDebitNote');
        }

        $changeStatusToGenerate = $request->request->get('changeStatusToGenerateAction');
        if ($changeStatusToGenerate) {
            /** @var SettlementPackage $settlementPackage */
            $package = $debitNotePackageModel->getRecord($changeStatusToGenerate);
            if ($package) {
                $package->setErrorMessage(null);
                $package->setStatus(SettlementPackageModel::STATUS_GENERATE);
                $em->persist($package);
                $em->flush();
            }

            return $this->redirectToRoute('actualToDebitNote');
        }

        $generateEnveloAction = $request->request->get('generateEnveloAction');
        if ($generateEnveloAction) {
            /** @var DebitNotePackage $package */
            $package = $debitNotePackageModel->getRecord($generateEnveloAction);
            if ($package) {
                $documents = [];
                /** @var DebitNotePackageRecord $packageRecord */
                foreach ($package->getPackageRecords() as $packageRecord) {
                    if ($packageRecord->getStatus() != DebitNotePackageRecordModel::STATUS_COMPLETE) {
                        continue;
                    }

                    $documents[] = $packageRecord->getDocument();
                }

                if (count($documents)) {
                    // todo
                    $enveloModel->generateForDebitNotes($package->getId(), $documents);
                }
            }
        }

        $downloadXlsx = $request->request->get('downloadXlsx');
        if ($downloadXlsx) {
            /** @var DebitNotePackage $package */
            $package = $debitNotePackageModel->getRecord($downloadXlsx);

            $spreadsheet = new Spreadsheet();
            $spreadsheet->getActiveSheet()->setCellValue('A1', 'Lp.');
            $spreadsheet->getActiveSheet()->setCellValue('B1', 'Indywidualny nr rachunku');
            $spreadsheet->getActiveSheet()->setCellValue('C1', 'Typ umowy');
            $spreadsheet->getActiveSheet()->setCellValue('D1', 'Marka');
            $spreadsheet->getActiveSheet()->setCellValue('E1', 'Numer umowy');
            $spreadsheet->getActiveSheet()->setCellValue('F1', 'Data podpisania umowy');
            $spreadsheet->getActiveSheet()->setCellValue('G1', 'Data obow. umowy od');
            $spreadsheet->getActiveSheet()->setCellValue('H1', 'Data obow. umowy do');
            $spreadsheet->getActiveSheet()->setCellValue('I1', 'Ilość miesięcy');
            $spreadsheet->getActiveSheet()->setCellValue('J1', 'Wysokość kary za mc');
            $spreadsheet->getActiveSheet()->setCellValue('K1', 'Kwota kary');
            $spreadsheet->getActiveSheet()->setCellValue('L1', 'Szablon dokumentu');
            $spreadsheet->getActiveSheet()->setCellValue('M1', 'Dokument');
            $spreadsheet->getActiveSheet()->setCellValue('N1', 'Data utworzenia rekordu');

            $index = 2;
            /** @var DebitNotePackageRecord $packageRecord */
            foreach ($package->getPackageRecords() as $packageRecord) {
                $spreadsheet->getActiveSheet()->setCellValue('A' . $index, $index - 1);
                $spreadsheet->getActiveSheet()->setCellValue('B' . $index, $packageRecord->getAccountNumberIdentifier());
                $spreadsheet->getActiveSheet()->setCellValue('C' . $index, $packageRecord->getContractType());
                $spreadsheet->getActiveSheet()->setCellValue('D' . $index, $packageRecord->getBrand());
                $spreadsheet->getActiveSheet()->setCellValue('E' . $index, $packageRecord->getContractNumber());
                $spreadsheet->getActiveSheet()->setCellValue('F' . $index, $packageRecord->getContractSignDate() ? $packageRecord->getContractSignDate()->format('d-m-Y') : '');
                $spreadsheet->getActiveSheet()->setCellValue('G' . $index, $packageRecord->getContractFromDate() ? $packageRecord->getContractFromDate()->format('d-m-Y') : '');
                $spreadsheet->getActiveSheet()->setCellValue('H' . $index, $packageRecord->getContractToDate() ? $packageRecord->getContractToDate()->format('d-m-Y') : '');
                $spreadsheet->getActiveSheet()->setCellValue('I' . $index, $packageRecord->getMonthsNumber());
                $spreadsheet->getActiveSheet()->setCellValue('J' . $index, $packageRecord->getPenaltyAmountPerMonth());
                $spreadsheet->getActiveSheet()->setCellValue('K' . $index, $packageRecord->getSummaryGrossValue());
                $spreadsheet->getActiveSheet()->setCellValue('L' . $index, $packageRecord->getDocumentTemplate());
                $spreadsheet->getActiveSheet()->setCellValue('M' . $index, $packageRecord->getDocument());
                $spreadsheet->getActiveSheet()->setCellValue('N' . $index, $packageRecord->getCreatedAt() ? $packageRecord->getContractSignDate()->format('d-m-Y H:i:s') : '');

                $index++;
            }

            $this->downloadSpreadsheetAsXlsx($spreadsheet);
        }


        $selectedRows = $request->get('selectedRows');
        if ($selectedRows) {
            $multiRollBack = $request->request->get('multiRollBack');
            if ($multiRollBack) {
                foreach ($selectedRows as $rowId) {
                    /** @var DebitNotePackage $package */
                    $package = $debitNotePackageModel->getRecord($rowId);
                    if ($package) {
                        $em->getConnection()->beginTransaction();
                        try {
                            $documents = [];
                            $clients = [];

                            $packageRecords = $package->getPackageRecords()->toArray();
                            /** @var DebitNotePackageRecord $packageRecord */
                            foreach ($packageRecords as $packageRecord) {
                                if ($packageRecord->getDocument()) {
                                    $documents[] = $packageRecord->getDocument();
                                }
                                $clients[] = $packageRecord->getClient();
                            }

                            if (count($documents)) {
                                foreach ($documents as $document) {
                                    // Dispatching the event
                                    $billingRecordGeneratedEvent = new BillingRecordGeneratedEvent($document);
                                    $this->container->get('event_dispatcher')->dispatch('billing_record.removed', $billingRecordGeneratedEvent);
                                }
                            }

                            // remove package
                            $package = $debitNotePackageModel->getRecord($package->getId());
                            $em->remove($package);
                            $em->flush();



                            $em->getConnection()->commit();


                            foreach ($clients as $client) {
                                $billingDocumentsObject = $initializer->init($client)->generate();
                                $billingDocumentsObject->updateDocumentsIsPaidState();
                            }

                            foreach ($documents as $document) {
                                $documentPath = $debitNoteModel->getDocumentPath($document, $easyAdminModel->getEntityDirectoryByEntityName('DebitNote'));
                                if (file_exists($documentPath . '.pdf')) {
                                    unlink($documentPath . '.pdf');
                                }
                                if (file_exists($documentPath . '.docx')) {
                                    unlink($documentPath . '.docx');
                                }
                            }
                        } catch (\Exception $e) {
                            $em->getConnection()->rollBack();
                        }
                    }
                }
            }
        }

        return $this->redirectToRoute('actualToDebitNote');
    }




    /**
     * @Route("/packagesToGenerateListPostAction", name="packagesToGenerateListPostAction")
     */
    public function packagesToGenerateListPostAction(Request $request, EntityManager $em, Initializer $initializer, EnveloModel $enveloModel, ColonnadeModel $colonnadeModel, InvoiceModel $invoiceModel, EasyAdminModel $easyAdminModel, \Wecoders\InvoiceBundle\Service\InvoiceModel $invoiceBundleInvoiceModel)
    {
        $kernelRootDir = $this->get('kernel')->getRootDir();

        $changeStatusToProcess = $request->request->get('changeStatusToProcessAction');
        if ($changeStatusToProcess) {
            /** @var PackageToGenerate $packageToGenerate */
            $packageToGenerate = $this->getDoctrine()->getRepository('WecodersEnergyBundle:PackageToGenerate')->find($changeStatusToProcess);
            if ($packageToGenerate) {
                $packageToGenerate->setErrorMessage(null);
                $packageToGenerate->setStatus(PackageToGenerateModel::STATUS_IN_PROCESS);
                $em->persist($packageToGenerate);
                $em->flush();
            }

            return $this->redirectToRoute('actualToInvoice');
        }

        $changeStatusToGenerate = $request->request->get('changeStatusToGenerateAction');
        if ($changeStatusToGenerate) {
            /** @var PackageToGenerate $packageToGenerate */
            $packageToGenerate = $this->getDoctrine()->getRepository('WecodersEnergyBundle:PackageToGenerate')->find($changeStatusToGenerate);
            if ($packageToGenerate) {
                $packageToGenerate->setErrorMessage(null);
                $packageToGenerate->setStatus(PackageToGenerateModel::STATUS_GENERATE);
                $em->persist($packageToGenerate);
                $em->flush();
            }

            return $this->redirectToRoute('actualToInvoice');
        }

        $generateEnveloAction = $request->request->get('generateEnveloAction');
        if ($generateEnveloAction) {
            /** @var PackageToGenerate $packageToGenerate */
            $packageToGenerate = $this->getDoctrine()->getRepository('WecodersEnergyBundle:PackageToGenerate')->find($generateEnveloAction);
            if ($packageToGenerate) {
                $documents = $this->getDoctrine()->getRepository('WecodersEnergyBundle:InvoiceProforma')->findBy(['id' => $packageToGenerate->getDocumentIds()]);
                $enveloModel->generate($packageToGenerate->getId(), $documents);
            }
        }

        $generateColonnadeAction = $request->request->get('generateColonnadeAction');
        if ($generateColonnadeAction) {
            /** @var PackageToGenerate $packageToGenerate */
            $packageToGenerate = $this->getDoctrine()->getRepository('WecodersEnergyBundle:PackageToGenerate')->find($generateColonnadeAction);
            if ($packageToGenerate) {
                $colonnadeModel->generateFromPackage($packageToGenerate);
            }
        }

        $selectedRows = $request->get('selectedRows');

        if ($selectedRows) {
            $multiRollBack = $request->request->get('multiRollBack');

            if ($multiRollBack) {
                $directoryRelative = $easyAdminModel->getEntityDirectoryRelativeByEntityName('InvoiceProformaEnergy');

                foreach ($selectedRows as $rowId) {
                    /** @var PackageToGenerate $packageToGenerate */
                    $packageToGenerate = $this->getDoctrine()->getRepository('WecodersEnergyBundle:PackageToGenerate')->find($rowId);
                    // todo: only apply for status completed / error
                    if ($packageToGenerate) {
                        $contracts = $this->getDoctrine()->getRepository('GCRMCRMBundle:' . ($packageToGenerate->getContractType() == 'GAS' ? 'ContractGas' : 'ContractEnergy'))->findBy(['id' => $packageToGenerate->getContractIds()]);
                        $documents = $this->getDoctrine()->getRepository('WecodersEnergyBundle:InvoiceProforma')->findBy(['id' => $packageToGenerate->getDocumentIds()]);

                        if ($contracts) {
                            $em->getConnection()->beginTransaction();
                            try {
                                // bring back invoicing periods to before state
                                /** @var ContractEnergyBase $contract */
                                foreach ($contracts as $contract) {
                                    // adds before invoice period and next
                                    $contract->setNextInvoicingPeriod($contract->getBeforeInvoicingPeriod());
                                    $contract->setBeforeInvoicingPeriod(null);
                                    $em->persist($contract);
                                    $em->flush();
                                }

                                // remove generated documents records
                                if (count($documents)) {
                                    foreach ($documents as $document) {

                                        // Dispatching the event
                                        $billingRecordGeneratedEvent = new BillingRecordGeneratedEvent($document);
                                        $this->container->get('event_dispatcher')->dispatch('billing_record.removed', $billingRecordGeneratedEvent);

                                        $em->remove($document);
                                        $em->flush();
                                    }
                                }

                                $em->remove($packageToGenerate);
                                $em->flush();

                                // update client invoices paid state
                                // gets clients and contracts
                                if ($contracts[0]->getType() == 'ENERGY') {
                                    $clientsAndContracts = $em->getRepository('GCRMCRMBundle:ClientAndContractEnergy')->findBy(['contract' => $contracts]);
                                } else {
                                    $clientsAndContracts = $em->getRepository('GCRMCRMBundle:ClientAndContractGas')->findBy(['contract' => $contracts]);
                                }
                                if (!$clientsAndContracts) {
                                    die('Wygląda na to, że w paczce są umowy, które nie są przypisane do klienta.');
                                }
                                foreach ($clientsAndContracts as $clientsAndContract) {
                                    $client = $clientsAndContract->getClient();
                                    if (!$client) {
                                        die('Wygląda na to, że w paczce są umowy, które nie są przypisane do klienta.');
                                    }

                                    $billingDocumentsObject = $initializer->init($client)->generate();
                                    $billingDocumentsObject->updateDocumentsIsPaidState();
                                }

                                $em->getConnection()->commit();


                                /** @var InvoiceProforma $document */
                                foreach ($documents as $document) {
                                    $invoicePath = $invoiceBundleInvoiceModel->fullInvoicePath($kernelRootDir, $document, $directoryRelative);
                                    if (file_exists($invoicePath . '.pdf')) {
                                        unlink($invoicePath . '.pdf');
                                    }
                                    if (file_exists($invoicePath . '.docx')) {
                                        unlink($invoicePath . '.docx');
                                    }
                                }
                            } catch (\Exception $e) {
                                $em->getConnection()->rollBack();
                            }
                        }
                    }
                }
            }
        }

        return $this->redirectToRoute('actualToInvoice');
    }

    /**
     * @Route("/clone-as-correction-energy-action", name="cloneAsCorrectionEnergy")
     * @Route("/clone-as-correction-energy-to-zero-action", name="cloneAsCorrectionEnergyToZero")
     */
    public function cloneAsCorrectionEnergyAction(
        Request $request,
        EasyAdminModel $easyAdminModel,
        InvoiceModel $invoiceModel
    )
    {
        $id = $request->query->get('id');
        $entity = $request->query->get('entity');

        $toZero = $request->get('_route') == 'cloneAsCorrectionEnergyToZero' ? true : false;
        $correction = $invoiceModel->createCorrectionObject($entity, $id, $toZero);

        // redirect to the 'edit' view of the given entity item
        return $this->redirectToRoute('easyadmin', array(
            'action' => 'edit',
            'id' => $correction->getId(),
            'entity' => $easyAdminModel->getCloneAsEntityByEntityName($entity),
        ));
    }

    /**
     * @Route("/admin/generate-invoice-document-energy", name="generateInvoiceDocumentEnergy")
     */
    public function generateInvoiceDocumentEnergyAction(Request $request, EntityManager $em, ContractModel $contractModel, InvoiceTemplateModel $invoiceTemplateModel, InvoiceData $invoiceData, \Wecoders\InvoiceBundle\Service\InvoiceModel $invoiceModel, EasyAdminModel $easyAdminModel, InvoiceModel $energyBundleInvoiceModel)
    {
        $id = $request->query->get('id');
        $entity = $request->query->get('entity');
        $entityClass = $easyAdminModel->getEntityClassByEntityName($entity);

        /** @var InvoiceInterface $invoice */
        $invoice = $em->getRepository($entityClass)->find($id);

        $kernelRootDir = $this->get('kernel')->getRootDir();

        /** @var InvoiceTemplate $invoiceTemplate */
        $invoiceTemplate = $invoice->getInvoiceTemplate();
        if (!$invoiceTemplate || !$invoiceTemplate->getFilePath() || !file_exists($invoiceTemplateModel->getTemplateAbsolutePath($invoiceTemplate->getFilePath()))) {
            die('Szablon faktury nie istnieje (sprawdź czy rekord faktury ma ustawiony szablon oraz czy rekord szablonu ma wgrany plik).');
        }
        $templateAbsolutePath = $invoiceTemplateModel->getTemplateAbsolutePath($invoiceTemplate->getFilePath());

        $directoryRelative = $easyAdminModel->getEntityDirectoryRelativeByEntityName($entity);
        $invoicePath = $invoiceModel->fullInvoicePath($kernelRootDir, $invoice, $directoryRelative);

        if ($invoice instanceof InvoiceCollective) {
            $generateDocumentMethod = $easyAdminModel->getEntityGenerateDocumentMethodByEntityName($entity);
            $energyBundleInvoiceModel->$generateDocumentMethod($invoice, $invoicePath, $templateAbsolutePath, 'ENERGY');
        } else {
            $contract = $contractModel->getContractByNumber($invoice->getContractNumber(), [
                'GCRMCRMBundle:ClientAndContractEnergy' => 'GCRMCRMBundle:ContractEnergy',
                'GCRMCRMBundle:ClientAndContractGas' => 'GCRMCRMBundle:ContractGas',
            ]);
            if (!$contract) {
                die('Nie znaleziono umowy na podstawie numeru na fakturze');
            }

            $generateDocumentMethod = $easyAdminModel->getEntityGenerateDocumentMethodByEntityName($entity);
            $energyBundleInvoiceModel->$generateDocumentMethod($invoice, $invoicePath, $templateAbsolutePath, $contract->getType());
        }

        // redirect to the 'edit' view of the given entity item
        return $this->redirectToRoute('easyadmin', array(
            'action' => 'edit',
            'id' => $id,
            'entity' => $request->query->get('entity'),
        ));
    }

    /**
     * @Route("/admin/generate-payment-document-energy", name="generatePaymentRequestDocument")
     */
    public function generatePaymentRequestDocumentEnergyAction(Request $request, EntityManager $em, InvoiceTemplateModel $invoiceTemplateModel, EasyAdminModel $easyAdminModel, PaymentRequestModel $paymentRequestModel, ContractModel $contractModel)
    {
        $id = $request->query->get('id');
        $entity = $request->query->get('entity');

        /** @var PaymentRequest $paymentRequest */
        $paymentRequest = $em->getRepository('WecodersEnergyBundle:PaymentRequest')->find($id);

        /** @var InvoiceTemplate $documentTemplate */
        $documentTemplate = $paymentRequest->getDocumentTemplate();
        if (!$documentTemplate || !$documentTemplate->getFilePath() || !file_exists($invoiceTemplateModel->getTemplateAbsolutePath($documentTemplate->getFilePath()))) {
            die('Szablon dokumentu nie istnieje');
        }
        $templateAbsolutePath = $invoiceTemplateModel->getTemplateAbsolutePath($documentTemplate->getFilePath());
        $documentPath = $paymentRequestModel->getDocumentPath($paymentRequest, $easyAdminModel->getEntityDirectoryByEntityName($entity));

        /** @var Client $client */
        $client = $paymentRequest->getClient();
        if (!$client) {
            die('Rekord nie ma przypisanego klienta.');
        }

        /** @var ContractEnergy $contract */
        $contract = $contractModel->getContractByNumber($paymentRequest->getContractNumber(), [
            'GCRMCRMBundle:ClientAndContractEnergy' => 'GCRMCRMBundle:ContractEnergy',
            'GCRMCRMBundle:ClientAndContractGas' => 'GCRMCRMBundle:ContractGas',
        ]);
        if (!$contract) {
            die('Nie można znaleźć umowy na podstawie numeru');
        }
        $brand = $contract->getBrand();
//        $logoFilename = $paymentRequestModel->getLogoFilenameByBrand($brand);
        $logoAbsolutePath = $paymentRequestModel->getLogoAbsolutePath($brand);

        $paymentRequestModel->generatePaymentRequestDocument($paymentRequest, $documentPath, $templateAbsolutePath, $logoAbsolutePath, $contract->getType());

        // redirect to the 'edit' view of the given entity item
        return $this->redirectToRoute('easyadmin', array(
            'action' => 'edit',
            'id' => $id,
            'entity' => $request->query->get('entity'),
        ));
    }

    /**
     * @Route("/admin/generate-debit-note-document-energy", name="generateDebitNoteDocumentEnergy")
     */
    public function generateDebitNoteDocumentAction(Request $request, EntityManager $em, InvoiceTemplateModel $invoiceTemplateModel, EasyAdminModel $easyAdminModel, DebitNoteModel $debitNoteModel, ContractModel $contractModel)
    {
        $id = $request->query->get('id');
        $entity = $request->query->get('entity');

        /** @var DebitNote $debitNote */
        $debitNote = $em->getRepository('WecodersEnergyBundle:DebitNote')->find($id);

        /** @var InvoiceTemplate $documentTemplate */
        $documentTemplate = $debitNote->getDocumentTemplate();
        if (!$documentTemplate || !$documentTemplate->getFilePath() || !file_exists($invoiceTemplateModel->getTemplateAbsolutePath($documentTemplate->getFilePath()))) {
            die('Szablon dokumentu nie istnieje');
        }
        $templateAbsolutePath = $invoiceTemplateModel->getTemplateAbsolutePath($documentTemplate->getFilePath());
        $documentPath = $debitNoteModel->getDocumentPath($debitNote, $easyAdminModel->getEntityDirectoryByEntityName($entity));

        /** @var Client $client */
        $client = $debitNote->getClient();
        if (!$client) {
            die('Rekord nie ma przypisanego klienta.');
        }

        /** @var ContractEnergy $contract */
        $contract = $contractModel->getContractByNumber($debitNote->getContractNumber(), [
            'GCRMCRMBundle:ClientAndContractEnergy' => 'GCRMCRMBundle:ContractEnergy',
            'GCRMCRMBundle:ClientAndContractGas' => 'GCRMCRMBundle:ContractGas',
        ]);
        if (!$contract) {
            die('Nie można znaleźć umowy na podstawie numeru');
        }
        $brand = $contract->getBrand();
        $logoAbsolutePath = $debitNoteModel->getLogoAbsolutePath($brand);

        $debitNoteModel->generateDebitNoteDocument($debitNote, $documentPath, $templateAbsolutePath, $logoAbsolutePath, $contract->getType());

        // redirect to the 'edit' view of the given entity item
        return $this->redirectToRoute('easyadmin', array(
            'action' => 'edit',
            'id' => $id,
            'entity' => $request->query->get('entity'),
        ));
    }

    /**
     * @Route("/admin/display-payment-request-document-action", name="displayPaymentRequestDocument")
     */
    public function displayPaymentDocumentAction(Request $request, EntityManager $em, EasyAdminModel $easyAdminModel)
    {
        $id = $request->query->get('id');
        $entity = $request->query->get('entity');

        /** @var PaymentRequest $paymentRequest */
        $paymentRequest = $em->getRepository('WecodersEnergyBundle:PaymentRequest')->find($id);

        $paymentRequestDate = $paymentRequest->getCreatedDate();
        $datePieces = explode('-', $paymentRequestDate->format('Y-m-d'));

        $path = $easyAdminModel->getEntityDirectoryByEntityName($entity) . '/';

        $fullPath = $path . $datePieces[0] . '/' . $datePieces[1] . '/' . $paymentRequest->getId();
        $fullInvoicePathWithExtension = $fullPath . '.pdf';

        if (file_exists($fullInvoicePathWithExtension)) {
            $fullPath = $fullInvoicePathWithExtension;
        }

        header('Content-type: application/pdf');
        header('Content-Disposition: inline; filename="' . $paymentRequest->getId() . '.pdf"');
        echo readfile($fullPath);
        die;
    }

    /**
     * @Route("/admin/display-debit-note-document-energy-action", name="displayDebitNoteDocumentEnergy")
     */
    public function displayDebitNoteDocumentAction(Request $request, EntityManager $em, EasyAdminModel $easyAdminModel)
    {
        $id = $request->query->get('id');
        $entity = $request->query->get('entity');

        /** @var DebitNote $debitNote */
        $debitNote = $em->getRepository('WecodersEnergyBundle:DebitNote')->find($id);

        $debitNoteDate = $debitNote->getCreatedDate();
        $datePieces = explode('-', $debitNoteDate->format('Y-m-d'));

        $path = $easyAdminModel->getEntityDirectoryByEntityName($entity) . '/';

        $fullPath = $path . $datePieces[0] . '/' . $datePieces[1] . '/' . $debitNote->getId();
        $fullPathWithExtension = $fullPath . '.pdf';

        if (file_exists($fullPathWithExtension)) {
            $fullPath = $fullPathWithExtension;
        }

        header('Content-type: application/pdf');
        header('Content-Disposition: inline; filename="' . $debitNote->getId() . '.pdf"');
        echo readfile($fullPath);
        die;
    }

    /**
     * @Route("/display-payment-request-from-custom-list", name="displayPaymentRequestFromCustomList")
     */
    public function displayInvoiceFromCustomListAction(Request $request, EasyAdminModel $easyAdminModel, EntityManager $em, PaymentRequestModel $paymentRequestModel)
    {
        $id = $request->query->get('id');
        $entity = $request->query->get('entity');

        $entityClass = $easyAdminModel->getEntityClassByEntityName($entity);
        $entityDirectory = $easyAdminModel->getEntityDirectoryByEntityName($entity);
        if (!$entityDirectory) {
            die('Brak ustawień "directory" w konfiguracji tabel. Skontaktuj się z administratorem.');
        }

        $paymentRequest = $em->getRepository($entityClass)->find($id);

        $paymentRequestDate = $paymentRequest->getCreatedDate();
        $datePieces = explode('-', $paymentRequestDate->format('Y-m-d'));

        $path = $easyAdminModel->getEntityDirectoryByEntityName($entity) . '/';

        $fullPath = $path . $datePieces[0] . '/' . $datePieces[1] . '/' . $paymentRequest->getId();
        $fullInvoicePathWithExtension = $fullPath . '.pdf';

        if (file_exists($fullInvoicePathWithExtension)) {
            $fullPath = $fullInvoicePathWithExtension;
        }

        header('Content-type: application/pdf');
        header('Content-Disposition: inline; filename="' . $paymentRequest->getId() . '.pdf"');
        echo readfile($fullPath);
        die;
    }

    /**
     * @Route("/admin/display-invoice-document-action", name="displayInvoiceDocument")
     */
    public function displayInvoiceDocumentAction(Request $request, EntityManager $em, EasyAdminModel $easyAdminModel)
    {
        $id = $request->query->get('id');
        $entity = $request->query->get('entity');
        $entityClass = $easyAdminModel->getEntityClassByEntityName($entity);

        /** @var InvoiceProforma $invoice */
        $invoice = $em->getRepository($entityClass)->find($id);

        $invoiceDate = $invoice->getCreatedDate();
        $datePieces = explode('-', $invoiceDate->format('Y-m-d'));

        $number = $invoice->getNumber();
        $invoiceFilename = str_replace('/', '-', $number);

        $invoicesPath = $easyAdminModel->getEntityDirectoryByEntityName($entity) . '/';

        $fullInvoicePath = $invoicesPath . $datePieces[0] . '/' . $datePieces[1] . '/' . $invoiceFilename;
        $fullInvoicePathWithExtension = $fullInvoicePath . '.pdf';

        if (file_exists($fullInvoicePathWithExtension)) {
            $fullInvoicePath = $fullInvoicePathWithExtension;
        }

        header('Content-type: application/pdf');
        header('Content-Disposition: inline; filename="' . $invoice->getNumber() . '.pdf"');
        echo readfile($fullInvoicePath);
        die;
    }

    /**
     * @Route("/authorization-department-energy", name="authorizationDepartmentEnergy")
     */
    public function authorizationDepartmentEnergyAction(
        Request $request,
        EntityManager $em,
        ModulesModel $modulesModel,
        AccountNumberMaker $accountNumberMaker
    )
    {
        $contractTypesDb = $em->getRepository('GCRMCRMBundle:ContractType')->findAll();
        if (!$contractTypesDb) {
            die('Contract types are not defined.');
        }

        $form = $this->createForm(AuthorizationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personType = $form->get('personType')->getData();

            if ($contractTypesDb && count($contractTypesDb) > 1) {
                $contractTypes = $form->get('contractTypes')->getData();
                if (!$contractTypes || !count($contractTypes)) {
                    $this->addFlash('notice', 'Musisz wybrać typ umowy.');
                    return $this->redirectToRoute('authorizationDepartmentEnergy');
                }
            } else { // only one is defined, so select it
                $contractTypes = [];
                $contractTypes[] = $contractTypesDb[0];
            }

            // when radio option, choosen option is not an array
            // make array from it
            if (is_object($contractTypes)) {
                $tmpContractTypes = $contractTypes;
                $contractTypes = [];
                $contractTypes[] = $tmpContractTypes;
            }

            /** @var StatusContract $statusAuthorizationObject */
            $statusAuthorizationObject = $form->get('statusAuthorization')->getData();
            if (!isset($statusAuthorizationObject) || $statusAuthorizationObject == null) {
                $this->addFlash('notice', 'Musisz wybrać status rozmowy.');
                return $this->redirectToRoute('authorizationDepartmentEnergy');
            }

            $signDate = $form->get('datepicker')->getData();
            if (!isset($signDate) || $signDate == null) {
                $this->addFlash('notice', 'Musisz wybrać datę podpisania umowy.');
                return $this->redirectToRoute('authorizationDepartmentEnergy');
            }

            $statusDepartment = $this->getDoctrine()->getRepository('GCRMCRMBundle:StatusDepartment')->findOneBy([
                'code' => 'verification'
            ]);
            if (!$statusDepartment) {
                die('Status dla departamentu weryfikacji nie został jeszcze zdefiniowany');
            }

            $em->getConnection()->beginTransaction();
            try {
                $client = new Client();
                $client->setUser($this->getUser());
                if ($personType == AuthorizationType::PERSON_TYPE_PERSON) {
                    $client->setName($form->get('name')->getData());
                    $client->setSurname($this->removeSpacesWhenSeparatorBeforeOrAfter($form->get('surname')->getData()));
                    $client->setPesel($form->get('pesel')->getData());
                    $client->setIsCompany(false);
                } elseif ($personType == AuthorizationType::PERSON_TYPE_COMPANY) {
                    $client->setCompanyName($form->get('companyName')->getData());
                    $client->setNip($form->get('companyNip')->getData());
                    $client->setIsCompany(true);
                }
                $client->setTelephoneNr($form->get('telephoneCallNumber')->getData());
                $client->setEmail($form->get('email')->getData());
                $client->setIsMarkedToGenerateInvoice(false);
                $client->setIsInvoiceGenerated(false);

                if (!$modulesModel->isEnabledBankAccountGeneratorFunctionality()) {
                    throw new \Exception('Bank account number generator is disabled');
                }

                $accountNumberMaker->append($client);

                $em->persist($client);

                $loopIndex = 0;
                /** @var \GCRM\CRMBundle\Entity\ContractType $contractType */
                foreach ($contractTypes as $contractType) {
                    /** @var ContractInterface $contract */
                    if ($contractType->getCode() == 'gas') {
                        $contract = new ContractGas();
                        $contract->setType('GAS');
                        $clientAndContractType = new ClientAndContractGas();
                        $priceList = $form->get('priceListGas')->getData();
                        $tariff = $form->get('tariffGas')->getData();
                        $contractAndPriceList = new ContractGasAndPriceList();
                        $contractAndSellerTariff = new ContractGasAndSellerTariff();
                        $contractAndDistributionTariff = new ContractGasAndDistributionTariff();
                    } elseif ($contractType->getCode() == 'energy') {
                        $contract = new ContractEnergy();
                        $contract->setType('ENERGY');
                        $clientAndContractType = new ClientAndContractEnergy();
                        $priceList = $form->get('priceListEnergy')->getData();
                        $tariff = $form->get('tariffEnergy')->getData();
                        $contractAndPriceList = new ContractEnergyAndPriceList();
                        $contractAndSellerTariff = new ContractEnergyAndSellerTariff();
                        $contractAndDistributionTariff = new ContractEnergyAndDistributionTariff();
                    } else {
                        continue;
                    }

                    $contractAndPriceList->setContract($contract);
                    $contractAndPriceList->setPriceList($priceList);
                    $contract->addContractAndPriceList($contractAndPriceList);

                    if ($form->has('brands')) {
                        $contract->setBrand($form->get('brands')->getData());
                    }

                    if ($form->get('isContractMultiPerson')->getData()) {
                        $contract->setSecondPersonName($form->get('secondPersonName')->getData());
                        $contract->setSecondPersonSurname($form->get('secondPersonSurname')->getData());
                        $contract->setSecondPersonPesel($form->get('secondPersonPesel')->getData());
                    }

                    $contract->setPpZipCode($form->get('ppZipCode')->getData());
                    $contract->setPpCity($form->get('ppCity')->getData());
                    $contract->setPpStreet($form->get('ppStreet')->getData());
                    $contract->setPpHouseNr($form->get('ppHouseNr')->getData());
                    $contract->setPpApartmentNr($form->get('ppApartmentNr')->getData());

                    $contract->setSalesRepresentative($form->get('salesRepresentative')->getData());

                    $contractAndSellerTariff->setTariff($tariff);
                    $contractAndDistributionTariff->setTariff($tariff);
                    $contract->addContractAndSellerTariff($contractAndSellerTariff);
                    $contract->addContractAndDistributionTariff($contractAndDistributionTariff);

                    $contract->setConsumption($form->get('consumption')->getData());
                    $contract->setPeriodInMonths($form->get('contractPeriodInMonths')->getData());
                    $contract->setContractNumber($form->get('contractNumber')->getData());
                    $contract->setUser($this->getUser());
                    $contract->setStatusDepartment($statusDepartment);
                    $contract->setStatusContractAuthorization($statusAuthorizationObject);
                    $contract->setCommentAuthorization($form->get('commentAuthorization')->getData());
                    $contract->setIsDownloaded(false);
                    $contract->setIsOnPackageList(false);
                    $contract->setIsReturned(false);

                    $contract->setIsResignation(false);
                    /** @var StatusContractAction $statusContractAction */
                    $statusContractAction = $statusAuthorizationObject->getStatusContractAction();
                    if ($statusContractAction) {
                        $code = $statusContractAction->getCode();
                        if ($code && $code == \GCRM\CRMBundle\Service\StatusContractAction::STATUS_RESIGN) {
                            $contract->setIsResignation(true);
                        }
                    }
                    $contract->setActualStatus($statusAuthorizationObject);
                    $contract->setIsBrokenContract(false);
                    $contract->setSignDate($signDate);

                    $em->persist($contract);

                    $clientAndContractType->setClient($client);
                    $clientAndContractType->setContract($contract);

                    $em->persist($clientAndContractType);

                    $loopIndex++;
                }

                $em->flush();
                $em->getConnection()->commit();

                $this->addFlash('success', 'Utworzono kartę klienta');
            } catch (\Exception $e) {
                $em->getConnection()->rollBack();
                $this->addFlash('error', 'Wystąpił błąd, zmiany nie zostały wprowadzone. ' . $e->getMessage());
            }

            return $this->redirectToRoute('authorizationDepartmentEnergy');
        }

        $historyRecords = $this->getDoctrine()->getRepository('GCRMCRMBundle:Client')->findBy([
            'user' => $this->getUser()
        ]);

        return $this->render('@GCRMCRM/Default/departments/authorization/energy-and-gas.html.twig', [
            'form' => $form->createView(),
            'historyRecords' => $historyRecords,
            'contractTypes' => $contractTypesDb,
        ]);
    }

    /**
     * Removes spaces from string if there is situation for example: 'string...  separator  string...'
     * It can be used for example to remove spaces from double surname: "John Doe - Super" -> "John Doe-Super"
     *
     * @param $string
     * @return string
     */
    private function removeSpacesWhenSeparatorBeforeOrAfter($string, $separator = '-')
    {
        if ($string === null || !mb_strlen($string)) {
            return $string;
        }

        $string = preg_replace('/([ ]*' . $separator . ')/', $separator, $string);
        $string = preg_replace('/(' . $separator . '[ ]*)/', $separator, $string);
        return preg_replace('/([ ]+)/', ' ', $string);
    }
}
