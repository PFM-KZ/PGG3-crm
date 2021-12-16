<?php

namespace GCRM\CRMBundle\Event;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\Company;
use GCRM\CRMBundle\Entity\Contract;
use GCRM\CRMBundle\Entity\ContractAndService;
use GCRM\CRMBundle\Entity\ContractEnergyBase;
use GCRM\CRMBundle\Entity\Invoice;
use GCRM\CRMBundle\Entity\InvoiceInterface;
use GCRM\CRMBundle\Entity\Payment;
use GCRM\CRMBundle\Entity\Service;
use GCRM\CRMBundle\Entity\StatusContract;
use GCRM\CRMBundle\Entity\StatusContractAction;
use GCRM\CRMBundle\Entity\StatusContractAuthorization;
use GCRM\CRMBundle\Entity\User;
use GCRM\CRMBundle\Entity\UserAndCompanyWithBranch;
use GCRM\CRMBundle\Service\AccountNumberIdentifierModel;
use GCRM\CRMBundle\Service\AccountNumberModel;
use GCRM\CRMBundle\Service\BillingDocument\Initializer;
use GCRM\CRMBundle\Service\ClientModel;
use GCRM\CRMBundle\Service\CompanyModel;
use GCRM\CRMBundle\Service\ContractModel;
use GCRM\CRMBundle\Service\GTU;
use Wecoders\EnergyBundle\Entity\InvoiceCollective;
use Wecoders\EnergyBundle\Service\ExciseModel;
use GCRM\CRMBundle\Service\InvoiceModel;
use JavierEguiluz\Bundle\EasyAdminBundle\Event\EasyAdminEvents;
use Pagerfanta\Pagerfanta;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Wecoders\EnergyBundle\Service\Facade\InvoiceUpdaterFacade;
use Wecoders\InvoiceBundle\Entity\InvoiceNumberSettings;
use Wecoders\InvoiceBundle\Service\Helper;
use Wecoders\InvoiceBundle\Service\InvoiceData;
use Wecoders\InvoiceBundle\Service\InvoiceProduct;
use Wecoders\InvoiceBundle\Service\InvoiceProductGroup;
use Wecoders\InvoiceBundle\Service\NumberModel;

// W korekcie rozliczenia, SettlementCorrection
class EasyAdminSubscriber implements EventSubscriberInterface
{
    private $em;

    private $templating;

    private $mailer;

    private $container;

    private $tokenStorage;

    private $invoiceModel;

    private $flashBag;

    private $initializer;

    private $clientModel;

    private $entity;

    private $exciseModel;

    private $accountNumberIdentifierModel;

    private $accountNumberModel;

    private $contractModel;

    private $gtuModel;

    private $invoiceUpdaterFacade;

    public function __construct(
        EntityManager $em,
        ContainerInterface $container,
        InvoiceModel $invoiceModel,
        FlashBag $flashBag,
        Initializer $initializer,
        ClientModel $clientModel,
        TokenStorageInterface $tokenStorage,
        ExciseModel $exciseModel,
        AccountNumberIdentifierModel $accountNumberIdentifierModel,
        AccountNumberModel $accountNumberModel,
        ContractModel $contractModel,
        GTU $gtuModel,
        InvoiceUpdaterFacade $invoiceUpdaterFacade
    )
    {
        $this->em = $em;
        $this->container = $container;
        $this->templating = $container->get('templating');
        $this->mailer = $container->get('mailer');
        $this->invoiceModel = $invoiceModel;
        $this->flashBag = $flashBag;
        $this->initializer = $initializer;
        $this->clientModel = $clientModel;
        $this->tokenStorage = $tokenStorage;
        $this->exciseModel = $exciseModel;
        $this->accountNumberIdentifierModel = $accountNumberIdentifierModel;
        $this->accountNumberModel = $accountNumberModel;
        $this->contractModel = $contractModel;
        $this->gtuModel = $gtuModel;
        $this->invoiceUpdaterFacade = $invoiceUpdaterFacade;
    }

    public function onPostSearch()
    {

    }

    public static function getSubscribedEvents()
    {
        return [
            EasyAdminEvents::PRE_PERSIST => 'onPrePersist',
            EasyAdminEvents::POST_PERSIST => 'onPostPersist',
            EasyAdminEvents::PRE_UPDATE => 'onPreUpdate',
            EasyAdminEvents::POST_UPDATE => 'onPostUpdate',
            EasyAdminEvents::POST_LIST => 'onPostList',
            EasyAdminEvents::POST_SEARCH => 'onPostSearch',
            EasyAdminEvents::PRE_DELETE => 'onPreDelete',
            EasyAdminEvents::POST_DELETE => 'onPostDelete',
            EasyAdminEvents::PRE_EDIT => 'onPreEdit',
        ];
    }

    /**
     * Function which clones the object before it gets edited, creating the "previous" object
     */
    public function onPreEdit(GenericEvent $event)
    {
        $class = $event->getSubject()["class"];
        $request = $event->getArgument('request');
        $id = $request->query->get('id');

        $entity = $this->em->getRepository($class)->find($id);
        $this->entity = clone $entity;
    }

    public function onPostList(GenericEvent $event)
    {
        $this->addLpNumberToEntityDesc($event->getSubject());
    }

    private function addLpNumberToEntityDesc(Pagerfanta $paginator)
    {
        if ($paginator->getCurrentPage() == 1) {
            $index = $paginator->getNbResults();
        } else {
            $index = $paginator->getNbResults() - ($paginator->getCurrentPage() - 1) * $paginator->getMaxPerPage();
        }
        foreach ($paginator as $item) {
            $item->lp = $index;
            $index--;
        }
    }

    public function onPreUpdate(GenericEvent $event)
    {
        /** @var EntityManager $em */
        $em = $event->getArgument('em');
        $entity = $event->getArgument('entity');
        /** @var Request $request */
        $request = $event->getArgument('request');

        // updates client recipient and payer data
        // reset checkboxes
        if (isset($entity) && get_class($entity) == 'GCRM\CRMBundle\Entity\Client') {
            /** @var Client $client */
            $client = $entity;

            $client->setPayerZipCode($client->getToPayerZipCode());
            $client->setPayerApartmentNr($client->getToPayerApartmentNr());
            $client->setPayerCity($client->getToPayerCity());
            $client->setPayerCompanyName($client->getToPayerCompanyName());
            $client->setPayerHouseNr($client->getToPayerHouseNr());
            $client->setPayerNip($client->getToPayerNip());
            $client->setPayerStreet($client->getToPayerStreet());

            $client->setRecipientZipCode($client->getToRecipientZipCode());
            $client->setRecipientApartmentNr($client->getToRecipientApartmentNr());
            $client->setRecipientCity($client->getToRecipientCity());
            $client->setRecipientCompanyName($client->getToRecipientCompanyName());
            $client->setRecipientHouseNr($client->getToRecipientHouseNr());
            $client->setRecipientNip($client->getToRecipientNip());
            $client->setRecipientStreet($client->getToRecipientStreet());

            $client->setIsPayerSameAsBuyer(false);
            $client->setIsPayerSameAsRecipient(false);
            $client->setIsRecipientSameAsBuyer(false);

            $this->em->persist($client);
        }



        // Updates summary on invoices
        // updates invoice paid state
        if (
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceSettlement') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceSettlementCorrection') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceEstimatedSettlement') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceEstimatedSettlementCorrection') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\Invoice') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceCorrection') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceProforma') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceProformaCorrection')
        ) {
            /** @var InvoiceInterface $invoice */
            $invoice = $entity;

            $this->calculateSummaryValuesInInvoiceType($invoice, $em, false);
            $this->updateDateOfPaymentIfNotSet($invoice, $em);

            // calculate consumption and excise
            $this->updateConsumptionAndExcise($invoice);

            if (
                isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceProformaCorrection')
            ) {
                if ($invoice->getSummaryGrossValue() <= $invoice->getPaidValue()) {
                    $invoice->setPaidValue($invoice->getSummaryGrossValue());
                    $invoice->setIsPaid(true);
                } elseif ($invoice->getPaidValue() < $invoice->getSummaryGrossValue()) {
                    $invoice->setIsPaid(false);
                }
            }

            $em->persist($invoice);
        } elseif (isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceCollective')) {
            $this->updateSummaryValuesOfInvoiceCollective($entity);
        }


        $requestEntity = $request->query->get('entity');
        if (
            $requestEntity == 'ContractGasProcessDepartment' ||
            $requestEntity == 'ContractEnergyProcessDepartment'
        ) {
            /** @var ContractEnergyBase $contract */
            $contract = $entity;
            /** @var StatusContract $status */
            $status = $contract->getStatusContractProcess();
            if ($status) {
                /** @var StatusContractAction $statusAction */
                $statusAction = $status->getStatusContractAction();
                if ($statusAction && $statusAction->getCode() == \GCRM\CRMBundle\Service\StatusContractAction::STATUS_GO) {
                    // check if all OSD fields are choosen
                    // and also check if date from and date to is choosen

                    $isError = false;

                    if (!$contract->getIsTerminationSent()) {
                        $isError = true;
                    }

                    if (!$contract->getTerminationCreatedDate()) {
                        $isError = true;
                    }

                    if (!$contract->getIsProposalOsdSent()) {
                        $isError = true;
                    }

                    if (!$contract->getProposalStatus()) {
                        $isError = true;
                    }

                    if (!$contract->getPlannedActivationDate()) {
                        $isError = true;
                    }

                    if (!$contract->getContractFromDate()) {
                        $isError = true;
                    }

                    if (!$contract->getContractToDate()) {
                        $isError = true;
                    }
                    // check date from and date to
                    if ($isError) {
                        die('Nie można przenieść umowy do departamentu finansowego. Pola OSD oraz daty obowiązywania omowy od-do muszą być uzupełnione oraz ustawione jako pozytywne.');
                    }
                }
            }
        }

        if (
            $requestEntity == 'ContractGasVerificationDepartment' ||
            $requestEntity == 'ContractEnergyVerificationDepartment'
        ) {
            /** @var ContractEnergyBase $contract */
            $contract = $entity;
            /** @var StatusContract $status */
            $status = $contract->getStatusContractVerification();
            if ($status) {
                /** @var StatusContractAction $statusAction */
                $statusAction = $status->getStatusContractAction();
                if ($statusAction && $statusAction->getCode() == \GCRM\CRMBundle\Service\StatusContractAction::STATUS_GO) {
                    /** @var StatusContractAuthorization $statusAuthorization */
                    $statusAuthorization = $contract->getStatusContractAuthorization();
                    if (!$statusAuthorization) {
                        die('Brak statusu autoryzacji.');
                    }

                    $code = $statusAuthorization->getStatusContractAction()->getCode();
                    if ($code != \GCRM\CRMBundle\Service\StatusContractAction::STATUS_GO) {
                        die('Błąd (zmiany nie zostały zapisane) - aby zmienić status weryfikacji na pozytywny - status autoryzacji musi być pozytywny.');
                    }
                }
            }
        }




        $dataProvider = ['Gas', 'Energy', 'Television', 'Internet', 'Wlr', 'Mvno', 'Fvno', 'Polmed'];
        foreach ($dataProvider as $contractType) {
            if (isset($entity) && (get_class($entity) == 'GCRM\CRMBundle\Entity\Contract' . $contractType)) {
                $this->contractModel->addLog($this->entity, $entity);
            }
        }
    }

    private function updateSummaryValuesOfInvoiceCollective(InvoiceCollective $invoice)
    {
        $data = $invoice->getInvoicesData();
        if ($data && count($data)) {
            foreach ($data as &$dataItem) {
                foreach ($dataItem['services'] as &$item) {
                    $invoiceProduct = new InvoiceProduct(new Helper());
                    $invoiceProduct->setNetValue($item['netValue']);
                    $invoiceProduct->setVatPercentage($item['vatPercentage']);
                    $item['grossValue'] = $invoiceProduct->getGrossValue();
                }
            }
            $invoice->setInvoicesData($data);
        }

        $data = $invoice->getData();
        $summary = [
            'netValue' => 0,
            'vatValue' => 0,
            'grossValue' => 0,
            'consumption' => 0,
            'exciseValue' => 0,
        ];

        foreach ($data as $item) {
            $summary['netValue'] += $item['netValue'];
            $summary['vatValue'] += $item['vatValue'];
            $summary['grossValue'] += $item['grossValue'];
            $summary['consumption'] += $item['consumption'];
            $summary['exciseValue'] += $item['exciseValue'];
        }

        $invoice->setSummaryNetValue($summary['netValue']);
        $invoice->setSummaryGrossValue($summary['grossValue']);
        $invoice->setSummaryVatValue($summary['vatValue']);
        $invoice->setConsumption($summary['consumption']);
        $invoice->setExciseValue($summary['exciseValue']);
    }

    public function onPostUpdate(GenericEvent $event)
    {
        /** @var EntityManager $em */
        $em = $event->getArgument('em');
        $entity = $event->getArgument('entity');

        // CHANGES STATUS DEPARTMENT FUNCTIONALITY AFTER STATUS CHOOSE AND SAVE
        $dataProvider = ['Gas', 'Energy', 'Television', 'Internet', 'Wlr', 'Mvno', 'Fvno', 'Polmed'];

        foreach ($dataProvider as $contractType) {
            if (isset($entity) && (get_class($entity) == 'GCRM\CRMBundle\Entity\Contract' . $contractType)) {
                $this->contractModel->onPostUpdate($entity);
            }
        }

        // Updates summary on invoice and invoice correction
        // updates invoice paid state
        if (
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceSettlement') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceSettlementCorrection') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceEstimatedSettlement') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceEstimatedSettlementCorrection') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\Invoice') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceCorrection') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceProforma') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceProformaCorrection')
        ) {
            /** @var InvoiceInterface $invoice */
            $invoice = $entity;

            // this method breaks if cannot generate number
//            $this->generateNumberIfNotSet($invoice, $em);

            $this->calculateSummaryValuesInInvoiceType($invoice, $em);
//            $this->updateClientInvoiceData($invoice, $em);
            $this->updateSellerInvoiceData($invoice, $em);
            $this->updateInvoiceNumberSettingsData($invoice, $em);
            $this->setDataIds($invoice, $em);
            $this->gtuModel->updateGTU($invoice, $invoice->getData());

            // reset additional data to null
            $invoice->setSeller(null);
//            $invoice->setClient(null);
            $invoice->setInvoiceNumberSettings(null);

            $this->updateDateOfPaymentIfNotSet($invoice, $em);


            // calculate consumption and excise
            $this->updateConsumptionAndExcise($invoice);


            $em->persist($invoice);
            $em->flush();

            $invoice = $entity;
            $client = $invoice->getClient();
            if ($client) {
                $billingDocumentsObject = $this->initializer->init($client)->generate();
                $billingDocumentsObject->updateDocumentsIsPaidState();
            }
        } elseif (isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceCollective')) {
            $this->updateSummaryValuesOfInvoiceCollective($entity);
        }

        if (
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\DebitNote')
        ) {
            $debitNote = $entity;
            $client = $debitNote->getClient();
            if ($client) {
                $billingDocumentsObject = $this->initializer->init($client)->generate();
                $billingDocumentsObject->updateDocumentsIsPaidState();
            }
        }

        $this->updateClientDocumentsByPayment($entity);
    }



    public function onPreDelete(GenericEvent $event)
    {
        /** @var EntityManager $em */
        $em = $event->getArgument('em');
        $entity = $event->getArgument('entity');

        /** @var Request $request */
        $request = $event->getArgument('request');

        $this->setDocumentClientIdInSession($request, $em, $entity);
    }

    public function onPostDelete(GenericEvent $event)
    {
        /** @var EntityManager $em */
        $em = $event->getArgument('em');
        $entity = $event->getArgument('entity');

        /** @var Request $request */
        $request = $event->getArgument('request');

        $this->updateClientDocumentsBySessionClientId($request, $em, $entity);

        $this->flashBag->add('success', 'Rekord został usunięty.');
    }

    private function updateClientDocumentsBySessionClientId(Request $request, EntityManager $em, $entity)
    {
        if (
            isset($entity) && isset($entity['class']) && $entity['class'] == 'Wecoders\EnergyBundle\Entity\InvoiceSettlement' ||
            isset($entity) && isset($entity['class']) && $entity['class'] == 'Wecoders\EnergyBundle\Entity\InvoiceSettlementCorrection' ||
            isset($entity) && isset($entity['class']) && $entity['class'] == 'Wecoders\EnergyBundle\Entity\InvoiceEstimatedSettlement' ||
            isset($entity) && isset($entity['class']) && $entity['class'] == 'Wecoders\EnergyBundle\Entity\InvoiceEstimatedSettlementCorrection' ||
            isset($entity) && isset($entity['class']) && $entity['class'] == 'Wecoders\EnergyBundle\Entity\Invoice' ||
            isset($entity) && isset($entity['class']) && $entity['class'] == 'Wecoders\EnergyBundle\Entity\InvoiceCorrection' ||
            isset($entity) && isset($entity['class']) && $entity['class'] == 'Wecoders\EnergyBundle\Entity\InvoiceProforma' ||
            isset($entity) && isset($entity['class']) && $entity['class'] == 'Wecoders\EnergyBundle\Entity\InvoiceProformaCorrection' ||
            isset($entity) && isset($entity['class']) && $entity['class'] == 'Wecoders\EnergyBundle\Entity\DebitNote' ||
            isset($entity) && isset($entity['class']) && $entity['class'] == 'GCRM\CRMBundle\Entity\Payment'
        ) {
            $session = $request->getSession();
            $clientId = $session->has('clientId') && $session->get('clientId') ? $session->get('clientId') : null;
            if ($clientId) {
                $client = $em->getRepository('GCRMCRMBundle:Client')->find($clientId);
                if ($client) {
                    $billingDocumentsObject = $this->initializer->init($client)->generate();
                    $billingDocumentsObject->updateDocumentsIsPaidState();
                }
                $session->remove('clientId');
            }
        }
    }

    private function setDocumentClientIdInSession(Request $request, EntityManager $em, $entity)
    {
        if (
            isset($entity) && isset($entity['class']) && $entity['class'] == 'Wecoders\EnergyBundle\Entity\InvoiceSettlement' ||
            isset($entity) && isset($entity['class']) && $entity['class'] == 'Wecoders\EnergyBundle\Entity\InvoiceSettlementCorrection' ||
            isset($entity) && isset($entity['class']) && $entity['class'] == 'Wecoders\EnergyBundle\Entity\InvoiceEstimatedSettlement' ||
            isset($entity) && isset($entity['class']) && $entity['class'] == 'Wecoders\EnergyBundle\Entity\InvoiceEstimatedSettlementCorrection' ||
            isset($entity) && isset($entity['class']) && $entity['class'] == 'Wecoders\EnergyBundle\Entity\Invoice' ||
            isset($entity) && isset($entity['class']) && $entity['class'] == 'Wecoders\EnergyBundle\Entity\InvoiceCorrection' ||
            isset($entity) && isset($entity['class']) && $entity['class'] == 'Wecoders\EnergyBundle\Entity\InvoiceProforma' ||
            isset($entity) && isset($entity['class']) && $entity['class'] == 'Wecoders\EnergyBundle\Entity\InvoiceProformaCorrection' ||
            isset($entity) && isset($entity['class']) && $entity['class'] == 'Wecoders\EnergyBundle\Entity\DebitNote'
        ) {
            $document = $em->getRepository($entity['class'])->find($request->query->get('id'));
            $client = $document->getClient();

            if ($client) {
                $session = $request->getSession();
                $session->set('clientId', $client->getId());
            }
        }

        if (isset($entity) && isset($entity['class']) && $entity['class'] == 'GCRM\CRMBundle\Entity\Payment') {
            /** @var Payment $record */
            $record = $em->getRepository($entity['class'])->find($request->query->get('id'));
            $client = $this->clientModel->getClientByBadgeId($record->getBadgeId());

            if ($client) {
                $session = $request->getSession();
                $session->set('clientId', $client->getId());
            }
        }
    }

    private function updateClientDocumentsByClientFromDocument($entity)
    {
        if (
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceSettlement') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceSettlementCorrection') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceEstimatedSettlement') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceEstimatedSettlementCorrection') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\Invoice') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceCorrection') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceProforma') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceProformaCorrection') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\DebitNote')
        ) {
            $document = $entity;
            $client = $document->getClient();
            if ($client) {
                $billingDocumentsObject = $this->initializer->init($client)->generate();
                $billingDocumentsObject->updateDocumentsIsPaidState();
            }
        }
    }

    private function setDataIds($objectToUpdate, EntityManager $em, $flush = true)
    {
        $data = $objectToUpdate->getData();
        $groupId = $this->getLastProductId($data);

        if ($data) {
            foreach ($data as $key => $item) {
                $serviceId = $this->getLastProductId($item['services']);
                if (isset($item['rabates'])) {
                    $rabateId = $this->getLastProductId($item['rabates']);
                }

                // sets group id
                if (!isset($data[$key]['id']) || !$data[$key]['id']) {
                    $data[$key]['id'] = ++$groupId;
                }

                // sets products id
                $products = $item['services'];
                if ($products) {
                    foreach ($products as $keyProduct => $product) {
                        if (!isset($product['id']) || !$product['id']) {
                            $data[$key]['services'][$keyProduct]['id'] = ++$serviceId;
                        }
                    }
                }

                // sets rabates id
                $rabates = isset($item['rabates']) ? $item['rabates'] : null;
                if ($rabates) {
                    foreach ($rabates as $keyRabate => $rabate) {
                        if (!isset($rabate['id']) || !$rabate['id']) {
                            $data[$key]['rabates'][$keyRabate]['id'] = ++$rabateId;
                        }
                    }
                }
            }

            $objectToUpdate->setData($data);

            $em->persist($objectToUpdate);
            if ($flush) {
                $em->flush();
            }
        }
    }

    private function getLastProductId($items)
    {
        $id = 0;

        if ($items && count($items)) {
            foreach ($items as $item) {
                $itemId = isset($item['id']) && $item['id'] ? $item['id'] : 0;
                $id = max($itemId, $id);
            }
        }

        return $id;
    }

    private function generateNumberIfNotSet($objectToUpdate, EntityManager $em, $flush = true)
    {
        $number = $objectToUpdate->getNumber();
        if ($number) {
            return;
        }

        // to generate number client must be set
        if (method_exists($objectToUpdate, 'getClient')) {
            /** @var Client $client */
            $client = $objectToUpdate->getClient();
            if ($client) {
                $errorMessage = 'Nie można wygenerować numeru. Sprawdź czy generowanie numeru proform zostało prawidłowo ustawione.';
                if (get_class($objectToUpdate) == 'Wecoders\EnergyBundle\Entity\Invoice') {
                    $type = 'invoice';
                    $table = 'WecodersEnergyBundle:Invoice';
                } elseif (get_class($objectToUpdate) == 'Wecoders\EnergyBundle\Entity\InvoiceCorrection') {
                    $type = 'invoice_correction';
                    $table = 'WecodersEnergyBundle:InvoiceCorrection';
                } elseif (get_class($objectToUpdate) == 'Wecoders\EnergyBundle\Entity\InvoiceProforma') {
                    $type = 'invoice_proforma';
                    $table = 'WecodersEnergyBundle:InvoiceProforma';
                } elseif (get_class($objectToUpdate) == 'Wecoders\EnergyBundle\Entity\InvoiceProformaCorrection') {
                    $type = 'invoice_proforma_correction';
                    $table = 'WecodersEnergyBundle:InvoiceProformaCorrection';
                } elseif (get_class($objectToUpdate) == 'Wecoders\EnergyBundle\Entity\InvoiceSettlement') {
                    $type = 'invoice_settlement';
                    $table = 'WecodersEnergyBundle:InvoiceSettlement';
                } elseif (get_class($objectToUpdate) == 'Wecoders\EnergyBundle\Entity\InvoiceSettlementCorrection') {
                    $type = 'invoice_settlement_correction';
                    $table = 'WecodersEnergyBundle:InvoiceSettlementCorrection';
                } elseif (get_class($objectToUpdate) == 'Wecoders\EnergyBundle\Entity\InvoiceEstimatedSettlement') {
                    $type = 'invoice_estimated_settlement';
                    $table = 'WecodersEnergyBundle:InvoiceEstimatedSettlement';
                } elseif (get_class($objectToUpdate) == 'Wecoders\EnergyBundle\Entity\InvoiceEstimatedSettlementCorrection') {
                    $type = 'invoice_estimated_settlement_correction';
                    $table = 'WecodersEnergyBundle:InvoiceEstimatedSettlementCorrection';
                } else {
                    die('Coś poszło nie tak. Skontaktuj się z administratorem.');
                }

                $kernelRootDir = $this->container->get('kernel')->getRootDir();
                $numberModel = new NumberModel();
                $numberModel->init($kernelRootDir, $em, $objectToUpdate->getCreatedDate());
                /** @var InvoiceNumberSettings $numberStructure */
                $numberStructure = $numberModel->getSettings($type);
                if (!$numberStructure) {
                    die('Opcje generowania numeru nie zostały ustawione.');
                }

                $tokensWithReplacement = [
                    [
                        'token' => '#id#',
                        'replacement' => $client->getAccountNumberIdentifier()->getNumber(), // badge id for example
                    ]
                ];
                $generatedNumber = $numberModel->generate($tokensWithReplacement, $table, 'number', $type);
                if (!$generatedNumber) {
                    die($errorMessage);
                }
                $objectToUpdate->setNumber($generatedNumber);

                $em->persist($objectToUpdate);
                if ($flush) {
                    $em->flush();
                }
                return;
            }
        }
        die('Aby wygenerować numer faktury niezbędne jest wybranie klienta z listy.');
    }

    private function updateSellerInvoiceData($objectToUpdate, EntityManager $em, $flush = true)
    {
        if (method_exists($objectToUpdate, 'getSeller')) {
            /** @var Company $seller */
            $seller = $objectToUpdate->getSeller();
            if (!$seller) {
                return;
            }

            $objectToUpdate->setSellerTitle($seller->getName());
            $objectToUpdate->setSellerAddress($seller->getAddress());
            $objectToUpdate->setSellerZipCode($seller->getZipcode());
            $objectToUpdate->setSellerCity($seller->getCity());
            $objectToUpdate->setSellerNip($seller->getNip());


            // update bank account data if client is choosen and bank data isnt set
            if (method_exists($objectToUpdate, 'getClient') && $objectToUpdate->getClient() && !$objectToUpdate->getSellerBankAccount() && !$objectToUpdate->getSellerBankName()) {
                /** @var Client $client */
                $client = $objectToUpdate->getClient();

                /** @var ClientModel $clientModel */
                $clientModel = $this->container->get('gcrm\crmbundle\service\clientmodel');
                /** @var CompanyModel $companyModel */
                $companyModel = $this->container->get('gcrm\crmbundle\service\companymodel');

                // Seller Bank Account Data
                $badgeId = $client->getAccountNumberIdentifier()->getNumber();
                /** @var Company $companyForBankAccountNumber */
                $companyForBankAccountNumber = $companyModel->getCompanyReadyForGenerateBankAccountNumbers();
                if ($badgeId && $companyForBankAccountNumber) {
                    $objectToUpdate->setSellerBankName($companyForBankAccountNumber->getBankName());
                    if ($clientModel->isValidBankAccountNumber($client->getBankAccountNumber())) {
                        $objectToUpdate->setSellerBankAccount($client->getBankAccountNumber());
                    } else {
                        die('Błędny numer rachunku klienta. Sprawdź czy unikalny numer rachunku został uzupełniony.');
                    }
                }
            }

            $em->persist($objectToUpdate);
            if ($flush) {
                $em->flush();
            }
        }
    }

    private function updateInvoiceNumberSettingsData($objectToUpdate, EntityManager $em, $flush = true)
    {
        if (method_exists($objectToUpdate, 'getInvoiceNumberSettings')) {
            /** @var InvoiceNumberSettings $invoiceNumberSettings */
            $invoiceNumberSettings = $objectToUpdate->getInvoiceNumberSettings();
            if (!$invoiceNumberSettings) {
                return;
            }

            $objectToUpdate->setNumberStructure($invoiceNumberSettings->getStructure());
            $objectToUpdate->setNumberLeadingZeros($invoiceNumberSettings->getLeadingZeros());
            $objectToUpdate->setNumberResetAiAtNewMonth($invoiceNumberSettings->getResetAiAtNewMonth());

            $em->persist($objectToUpdate);
            if ($flush) {
                $em->flush();
            }
        }
    }

    private function calculateSummaryValuesInInvoiceType($objectToUpdate, EntityManager $em, $flush = true)
    {
        $data = $objectToUpdate->getData();

        if ($data) {
            $invoiceData = new InvoiceData(new Helper());

            $invoiceProductGroups = [];
            foreach ($data as $item) {
                $products = $item['services'];
                $invoiceProductGroup = new InvoiceProductGroup();

                $invoiceProducts = [];
                if ($products) {
                    foreach ($products as $product) {
                        $invoiceProduct = new InvoiceProduct(new Helper());
                        $invoiceProduct->setNetValue($product['netValue']);
                        $invoiceProduct->setVatPercentage($product['vatPercentage']);
                        $invoiceProduct->setGrossValue($product['grossValue']);
                        $invoiceProducts[] = $invoiceProduct;
                    }
                    if (count($invoiceProducts)) {
                        $invoiceProductGroup->setProducts($invoiceProducts);
                    }
                }

                $rabates = isset($item['rabates']) ? $item['rabates'] : null;
                $invoiceRabates = [];
                if ($rabates) {
                    foreach ($rabates as $rabate) {
                        $invoiceProduct = new InvoiceProduct(new Helper());
                        $invoiceProduct->setNetValue($rabate['netValue']);
                        $invoiceProduct->setVatPercentage($rabate['vatPercentage']);
                        $invoiceProduct->setGrossValue($rabate['grossValue']);
                        $invoiceRabates[] = $invoiceProduct;
                    }
                    if (count($invoiceRabates)) {
                        $invoiceProductGroup->setRabates($invoiceRabates);
                    }
                }

                if (count($invoiceProducts) || count($invoiceRabates)) {
                    $invoiceProductGroups[] = $invoiceProductGroup;
                }
            }
            $invoiceData->setProductGroups($invoiceProductGroups);
            $vatGroups = $invoiceData->getProductsGroupsSummaryGroupedByVat();

            $objectToUpdate->setSummaryNetValue($vatGroups['summary']['netValue']);
            $objectToUpdate->setSummaryVatValue($vatGroups['summary']['vatValue']);
            $objectToUpdate->setSummaryGrossValue($vatGroups['summary']['grossValue']);

            $em->persist($objectToUpdate);
            if ($flush) {
                $em->flush();
            }
        }
    }

    public function vatValue($netValue, $grossValue = 0, $vatPercentage)
    {
        $vatValue = 0;

        if ($netValue > 0 && !$grossValue && $vatPercentage) {
            $vatValue = str_replace(',', '', number_format(($netValue * $vatPercentage / 100), 2));
        } elseif (!$netValue && $grossValue > 0 && $vatPercentage) {
            $vatValue = str_replace(',', '', number_format(($grossValue - $grossValue / ($vatPercentage / 100 + 1)), 2));
        }

        return $vatValue;
    }

    public function onPostPersist(GenericEvent $event)
    {
        /** @var EntityManager $em */
        $entity = $event->getArgument('entity');

        $this->updateClientDocumentsByClientFromDocument($entity);
        $this->updateClientDocumentsByPayment($entity);
    }

    private function updateClientDocumentsByPayment($entity)
    {
        if (isset($entity) && (get_class($entity) == 'GCRM\CRMBundle\Entity\Payment')) {
            /** @var Payment $payment */
            $payment = $entity;
            /** @var Client $client */
            $client = $this->clientModel->getClientByBadgeId($payment->getBadgeId());
            if ($client) {
                $billingDocumentsObject = $this->initializer->init($client)->generate();
                $billingDocumentsObject->updateDocumentsIsPaidState();
            }
        }
    }

    public function onPrePersist(GenericEvent $event)
    {
        /** @var EntityManager $em */
        $em = $event->getArgument('em');
        $entity = $event->getArgument('entity');

        if (isset($entity) && get_class($entity) == 'GCRM\CRMBundle\Entity\User') {
            /** @var User $user */
            $user = $entity;
            // updates password if not set
            if ($user->getPlainPassword() === null || ($user->getPlainPassword() && !strlen($user->getPlainPassword()))) {
                $user->setPlainPassword(md5(time() . rand(1, 9999)));
            }
        }

        // check if client is added
        if (isset($entity) && get_class($entity) == 'GCRM\CRMBundle\Entity\Client') {
            /** @var Client $client */
            $client = $entity;

            $modulesModel = $this->container->get('gcrm\crmbundle\service\modulesmodel');
            $companyModel = $this->container->get('gcrm\crmbundle\service\companymodel');

            if ($modulesModel->isEnabledBankAccountGeneratorFunctionality()) {
                $number = $this->accountNumberIdentifierModel->generateNumber();

                /** @var Company $companyForBankAccountNumber */
                $companyForBankAccountNumber = $companyModel->getCompanyReadyForGenerateBankAccountNumbers();
                $bankAccountNumber = $this->accountNumberModel->generateBankAccountNumber(
                    $companyForBankAccountNumber->getBankGeneratorStaticPartCodeOne() . $companyForBankAccountNumber->getBankGeneratorStaticPartCodeTwo(),
                    $number
                );

                $accountNumberIdentifier = $this->accountNumberIdentifierModel->add($number);

                $client->setAccountNumberIdentifier($accountNumberIdentifier);
                $client->setBankAccountNumber($bankAccountNumber);
            }
        }

        // Updates summary on invoice and invoice correction
        if (
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceSettlement') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceSettlementCorrection') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceEstimatedSettlement') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceEstimatedSettlementCorrection') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\Invoice') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceCorrection') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceProforma') ||
            isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceProformaCorrection')
        ) {
            /** @var InvoiceInterface $invoice */
            $invoice = $entity;
            $invoice->setIsPaid(false);

            // this method breaks if cannot generate number
            $this->updateInvoiceNumberSettingsData($invoice, $em, false);

            $this->generateNumberIfNotSet($invoice, $em, false);
            $this->setDataIds($invoice, $em, false);
            $this->gtuModel->updateGTU($invoice, $invoice->getData(), false);
            $this->calculateSummaryValuesInInvoiceType($invoice, $em, false);

            $this->invoiceUpdaterFacade->insertDataByClient($invoice, false);
            $this->updateSellerInvoiceData($invoice, $em, false);
            $this->updateDateOfPaymentIfNotSet($invoice, $em, false);

            // calculate consumption and excise
            $this->updateConsumptionAndExcise($invoice);

            // reset additional data to null
            $invoice->setSeller(null);
            $invoice->setInvoiceNumberSettings(null);
        } elseif (isset($entity) && (get_class($entity) == 'Wecoders\EnergyBundle\Entity\InvoiceCollective')) {
            $this->updateSummaryValuesOfInvoiceCollective($entity);
        }
    }

    private function updateConsumptionAndExcise($invoice)
    {
        // calculate consumption and excise
        $invoice->recalculateConsumption();
        if ($invoice->getType() == 'ENERGY') {
            if (!$invoice->getExcise()) {
                $exciseValue = $this->exciseModel->getExciseValueByDate($invoice->getBillingPeriodFrom());
                $invoice->setExcise($exciseValue);
            }
            $invoice->recalculateExciseValue();
        } else {
            $invoice->setExcise(0);
            $invoice->setExciseValue(0);
        }
    }

    private function updateDateOfPaymentIfNotSet($objectToUpdate, EntityManager $em, $flush = true)
    {
        if (!$objectToUpdate->getDateOfPayment()) {
            /** @var \DateTime $date */
            $date = clone $objectToUpdate->getCreatedDate();
            $date->modify('+14 days');
            $date->setTime(0, 0, 0);
            $objectToUpdate->setDateOfPayment($date);

            $em->persist($objectToUpdate);
            if ($flush) {
                $em->flush();
            }
        }
    }

}
