<?php

namespace GCRM\CRMBundle\Controller;

use AppBundle\Form\EntityMaxResultsType;
use AppBundle\Form\MassCorrectionType;
use AppBundle\Form\MassCustomDocumentType;
use AppBundle\Service\UploadedFileHelper;
use stringEncode\Exception;
use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\User;
use Symfony\Component\Form\Form;
use GCRM\CRMBundle\Entity\Branch;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\Company;
use GCRM\CRMBundle\Entity\Invoice;
use GCRM\CRMBundle\Entity\Payment;
use GCRM\CRMBundle\Entity\Service;
use GCRM\CRMBundle\Entity\Contract;
use GCRM\CRMBundle\Entity\Invoices;
use GCRM\CRMBundle\Service\Balance;
use GCRM\CRMBundle\Service\ZipModel;
use GCRM\CRMBundle\Form\SendFileType;
use GCRM\CRMBundle\Service\DataModel;
use GCRM\CRMBundle\Service\UserModel;
use GCRM\CRMBundle\Entity\ContractGas;
use GCRM\CRMBundle\Form\ListActionType;
use GCRM\CRMBundle\Form\ListSearchType;
use GCRM\CRMBundle\Form\StatisticsType;
use GCRM\CRMBundle\Service\ClientModel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use GCRM\CRMBundle\Entity\PackageToSend;
use GCRM\CRMBundle\Form\AddContractType;
use GCRM\CRMBundle\Form\ImporterGasType;
use GCRM\CRMBundle\Form\MultiExportType;
use GCRM\CRMBundle\Service\CompanyModel;
use GCRM\CRMBundle\Service\DataUploader;
use GCRM\CRMBundle\Service\InvoiceModel;
use GCRM\CRMBundle\Service\PaymentModel;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use GCRM\CRMBundle\Entity\ContractEnergy;
use GCRM\CRMBundle\Form\ImporterMainType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use GCRM\CRMBundle\Entity\InvoiceProforma;
use GCRM\CRMBundle\Entity\InvoiceTemplate;
use GCRM\CRMBundle\Entity\PaymentOldEnrex;
use GCRM\CRMBundle\Service\EasyAdminModel;
use GCRM\CRMBundle\Service\ListDownloader;
use GCRM\CRMBundle\Service\SlashPathModel;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use TZiebura\ExporterBundle\Service\DataExporter\XlsExporter;
use Wecoders\EnergyBundle\Entity\CustomDocumentTemplate;
use Wecoders\EnergyBundle\Entity\DocumentPackageToGenerate;
use Wecoders\EnergyBundle\Entity\DocumentPackageToGenerateRecord;
use Wecoders\EnergyBundle\Form\ButtonClickType;
use Wecoders\EnergyBundle\Form\FileUploadType;
use Wecoders\EnergyBundle\Service\CustomDocumentTemplateModel;
use Wecoders\EnergyBundle\Service\DocumentModel;
use Wecoders\EnergyBundle\Service\DocumentPackageToGenerateModel;
use Wecoders\EnergyBundle\Service\DocumentPackageToGenerateRecordModel;
use Wecoders\EnergyBundle\Service\EnveloModel;
use Wecoders\EnergyBundle\Service\SpreadsheetReader;
use Wecoders\InvoiceBundle\Service\Helper;
use GCRM\CRMBundle\Entity\InvoiceInterface;
use GCRM\CRMBundle\Entity\PackageToProcess;
use GCRM\CRMBundle\Entity\StatusDepartment;
use GCRM\CRMBundle\Form\ImporterEnergyType;
use GCRM\CRMBundle\Service\StatisticsModel;
use GCRM\CRMBundle\Entity\ContractInterface;
use GCRM\CRMBundle\Entity\InvoiceCorrection;
use GCRM\CRMBundle\Entity\PackageToGenerate;
use GCRM\CRMBundle\Form\CleanerMainDataType;
use GCRM\CRMBundle\Service\FileActionsModel;
use GCRM\CRMBundle\Service\ListDataExporter;
use GCRM\CRMBundle\Entity\ContractAndService;
use GCRM\CRMBundle\Form\ImporterPaymentsType;
use Symfony\Component\HttpFoundation\Request;
use Wecoders\InvoiceBundle\Service\Generator;
use GCRM\CRMBundle\Service\PackageToSendModel;
use GCRM\CRMBundle\Service\ValidateRoleAccess;
use Symfony\Component\HttpFoundation\Response;
use Wecoders\InvoiceBundle\Service\FooterData;
use GCRM\CRMBundle\Entity\ClientAndContractGas;
use GCRM\CRMBundle\Entity\ContractAndTelephone;
use GCRM\CRMBundle\Entity\StatusAdministration;
use GCRM\CRMBundle\Event\PaymentsUploadedEvent;
use GCRM\CRMBundle\Service\StatusContractModel;
use Wecoders\InvoiceBundle\Service\InvoiceData;
use Wecoders\InvoiceBundle\Service\NumberModel;
use GCRM\CRMBundle\Form\ImporterPaymentsOldType;
use Wecoders\InvoiceBundle\Service\InvoicePerson;
use GCRM\CRMBundle\Entity\ClientAndContractEnergy;
use TZiebura\ExporterBundle\Service\ExportHandler;
use Wecoders\InvoiceBundle\Service\InvoiceProduct;
use GCRM\CRMBundle\Service\AccessRestrictedException;
use GCRM\CRMBundle\Service\ListDataExporterInterface;
use GCRM\CRMBundle\Form\AuthorizationEnergyAndGasType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Wecoders\EnergyBundle\Service\PaymentRequestModel;
use GCRM\CRMBundle\Service\BillingDocument\Initializer;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Wecoders\InvoiceBundle\Service\InvoiceProductGroup;
use Wecoders\InvoiceBundle\Entity\InvoiceNumberSettings;
use EasyCorp\Bundle\EasyAdminBundle\Event\EasyAdminEvents;
use GCRM\CRMBundle\Entity\ContractAndTelephoneWithPackage;
use GCRM\CRMBundle\Entity\ContractAndTelephoneWithService;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use TZiebura\SmsBundle\Interfaces\SmsClientGroupInterface;
use GCRM\CRMBundle\Service\ListSearcherStrategyInitializer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TZiebura\ExporterBundle\Service\DataExporter\CsvExporter;
use TZiebura\ExporterBundle\Service\DataExporter\XlsxExporter;
use TZiebura\CorrespondenceBundle\Service\ListSearcher\Thread;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use GCRM\CRMBundle\Form\Depaprtments\Authorization\EnergyAndGasType;
use GCRM\CRMBundle\Service\ListSearcher\EntityListSearcherInterface;
use JavierEguiluz\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AdminController as BaseController;

class AdminController extends BaseController
{
    // deprecated
    const ROOT_RELATIVE_INVOICES_PATH = 'var/data/uploads/invoices/';

    const ROOT_RELATIVE_INVOICES_NEW_VERSION_PATH = 'var/data/uploads/invoices-new-version/';
    const ROOT_RELATIVE_CORRECTIONS_NEW_VERSION_PATH = 'var/data/uploads/corrections-new-version/';
    const ROOT_RELATIVE_INVOICES_PROFORMA_PATH = 'var/data/uploads/invoices-proforma/';

    const ROOT_RELATIVE_INVOICES_INPUT_PATH = 'var/data/uploads/invoicesInput/';

    const INVOICES_GMH_CODE = 'gmh';
    const INVOICES_GMH_DIRNAME = 'gmh';
    const INVOICES_CUM_CODE = 'cum';
    const INVOICES_CUM_DIRNAME = 'cum';

    const BRANCH_OPERATOR_CODE = 'BO';
    const BRANCH_CENTRAL_CODE = 'BC';
    const BRANCH_REGIONAL_CODE = 'BR';

    const ROLE_FINANCES_STATISTICS = 'ROLE_FINANCES_STATISTICS';
    const ROLE_ADMINISTRATION_STATISTICS = 'ROLE_ADMINISTRATION_STATISTICS';

    private $zipModel;
    private $invoiceModel;
    private $wecodersInvoiceModel;
    private $documentPackageToGenerateModel;
    private $documentModel;
    private $uploadedFileHelper;
    private $spreadsheetReader;
    private $clientModel;
    private $customDocumentTemplateModel;
    private $enveloModel;
    private $easyAdminModel;

    public function __construct(
        ZipModel $zipModel,
        InvoiceModel $invoiceModel,
        \Wecoders\EnergyBundle\Service\InvoiceModel $wecodersInvoiceModel,
        DocumentPackageToGenerateModel $documentPackageToGenerateModel,
        DocumentModel $documentModel,
        UploadedFileHelper $uploadedFileHelper,
        SpreadsheetReader $spreadsheetReader,
        ClientModel $clientModel,
        CustomDocumentTemplateModel $customDocumentTemplateModel,
        EnveloModel $enveloModel,
        EasyAdminModel $easyAdminModel
    )
    {
        $this->zipModel = $zipModel;
        $this->invoiceModel = $invoiceModel;
        $this->wecodersInvoiceModel = $wecodersInvoiceModel;
        $this->documentPackageToGenerateModel = $documentPackageToGenerateModel;
        $this->documentModel = $documentModel;
        $this->uploadedFileHelper = $uploadedFileHelper;
        $this->spreadsheetReader = $spreadsheetReader;
        $this->clientModel = $clientModel;
        $this->customDocumentTemplateModel = $customDocumentTemplateModel;
        $this->enveloModel = $enveloModel;
        $this->easyAdminModel = $easyAdminModel;
    }

    public function sendSmsAction()
    {
        $id = $this->request->query->get('id');
        $entity = $this->entity['class'];
        /** @var SmsClientGroupInterface $clientGroup */
        $clientGroup = $this->getDoctrine()->getRepository($entity)->find($id);
        $report = $this->smsSender->sendToGroup($clientGroup);

        $this->addFlash('success', 'Wiadomości SMS zostały wysłane, wysłano ' . $report->getSent() . ', niewysłano ' . $report->getFailed());
        
        return $this->redirectToReferrer();
    }

    /**
     * @Route("/general", name="general")
     */
    public function generalAction(Request $request, EntityManager $em)
    {
        return $this->render('@GCRMCRM/Default/general-by-roles.html.twig', []);
    }

    public function createContractEnergyAdministrationDepartmentEntityFormBuilder($entity, $view)
    {
        $formBuilder = parent::createEntityFormBuilder($entity, $view);
        return $this->updateSalesRepresentativeField($formBuilder);
    }
    public function createContractEnergyControlDepartmentEntityFormBuilder($entity, $view)
    {
        $formBuilder = parent::createEntityFormBuilder($entity, $view);
        return $this->updateSalesRepresentativeField($formBuilder);
    }
    public function createContractEnergyProcessDepartmentEntityFormBuilder($entity, $view)
    {
        $formBuilder = parent::createEntityFormBuilder($entity, $view);
        return $this->updateSalesRepresentativeField($formBuilder);
    }

    public function createContractGasAdministrationDepartmentEntityFormBuilder($entity, $view)
    {
        $formBuilder = parent::createEntityFormBuilder($entity, $view);
        return $this->updateSalesRepresentativeField($formBuilder);
    }
    public function createContractGasControlDepartmentEntityFormBuilder($entity, $view)
    {
        $formBuilder = parent::createEntityFormBuilder($entity, $view);
        return $this->updateSalesRepresentativeField($formBuilder);
    }
    public function createContractGasProcessDepartmentEntityFormBuilder($entity, $view)
    {
        $formBuilder = parent::createEntityFormBuilder($entity, $view);
        return $this->updateSalesRepresentativeField($formBuilder);
    }

    private function updateSalesRepresentativeField($formBuilder)
    {
        $agents = $this->em->getRepository('GCRMCRMBundle:User')->findBy([
            'isSalesRepresentative' => true
        ]);

        $formBuilder->add('salesRepresentative', ChoiceType::class, [
            'label' => 'Przedstawiciel handlowy',
            'choices' => $agents,
            'required' => false,
            'choice_value' => function ($entity = null) {
                return $entity ? $entity->getId() : '';
            },
            'choice_label' => function ($entity = null) {
                return $entity ?: '';
            },
            'placeholder' => 'Wybierz...',
        ]);

        return $formBuilder;
    }

    /**
     * {@inheritdoc}
     */
    protected function createSearchQueryBuilder($entityClass, $searchQuery, array $searchableFields, $sortField = null, $sortDirection = null, $dqlFilter = null)
    {
        $queryBuilder = $this->get('easyadmin.query_builder')->createSearchQueryBuilder($this->entity, $searchQuery, $sortField, $sortDirection, $dqlFilter);
        $lowerSearchQuery = mb_strtolower($searchQuery);

        $queryParameters = [];
        foreach ($this->entity['search']['fields'] as $name => $metadata) {
            if ('association' === $metadata['dataType']) {
                if (false === array_key_exists('searchField', $metadata)) {
                    throw new MissingSearchAssociationException($this->entity['name'], $name);
                }

                // Join the associated entity and search on the given field
                $searchFields = $metadata['searchField'];
                $queryBuilder->join(sprintf('entity.%s', $name), $name);

                // here is the change
                if (!is_array($searchFields)) {
                    $searchFields = [$searchFields];
                }
                foreach ($searchFields as $searchField) {
                    $queryBuilder->orWhere(sprintf('LOWER(%s.%s) LIKE :fuzzy_query', $name, $searchField));
                    $queryParameters['fuzzy_query'] = '%' . $lowerSearchQuery . '%';

                    $queryBuilder->orWhere(sprintf('LOWER(%s.%s) IN (:words_query)', $name, $searchField));
                    $queryParameters['words_query'] = explode(' ', $lowerSearchQuery);
                }
            }
        }

        if (0 !== count($queryParameters)) {
            $queryBuilder->setParameters($queryParameters);
        }

        return $queryBuilder;
    }

    /**
     * The method that is executed when the user performs a 'list' action on an entity.
     *
     * @return Response
     */
    protected function listAction()
    {
        $dqlOr = [];
        $dqlAnd = [];

        if ($this->request->query->get('entity') == 'PackageToSend') {
            // For administration department
            if ($this->request->query->has('statusDepartment') && $this->request->query->get('statusDepartment') == 'administration') {
                if ($this->request->query->has('packagesType') && $this->request->query->get('packagesType') == 'sent') {
                    $branchesFrom = $this->getDoctrine()->getRepository('GCRMCRMBundle:Branch')->findBy([
                        'typeCode' => [self::BRANCH_REGIONAL_CODE, self::BRANCH_CENTRAL_CODE]
                    ]);

                    /** @var Branch $branch */
                    foreach ($branchesFrom as $branch) {
                        $dqlOr[] = ' entity.fromBranch = ' . $branch->getId() . ' ';
                    }
                } elseif ($this->request->query->has('packagesType') && $this->request->query->get('packagesType') == 'delivered') {
                    $branches = $this->getDoctrine()->getRepository('GCRMCRMBundle:Branch')->findBy([
                        'typeCode' => [self::BRANCH_REGIONAL_CODE, self::BRANCH_CENTRAL_CODE]
                    ]);

                    $tempQueryFrom = [];
                    $tempQueryTo = [];
                    /** @var Branch $branch */
                    foreach ($branches as $branch) {
                        $tempQueryFrom[] = ' entity.fromBranch = ' . $branch->getId() . ' ';
                        $tempQueryTo[] = ' entity.toBranch = ' . $branch->getId() . ' ';
                    }

                    $query = ' (entity.isReturned = 0 OR entity.isReturned IS NULL)';
                    if (count($tempQueryFrom) || count($tempQueryTo)) {
                        $query .= ' AND ';
                    }
                    if (count($tempQueryFrom)) {
                        $query .= '(';
                        $query .= implode(' OR ', $tempQueryFrom);
                        $query .= ')';
                    }
                    if (count($tempQueryTo)) {
                        $query .= ' AND ';
                        $query .= '(';
                        $query .= implode(' OR ', $tempQueryTo);
                        $query .= ')';
                    }

                    $dqlAnd[] = $query;
                } elseif ($this->request->query->has('packagesType') && $this->request->query->get('packagesType') == 'returned') {
                    $branchesTo = $this->getDoctrine()->getRepository('GCRMCRMBundle:Branch')->findBy([
                        'typeCode' => [self::BRANCH_REGIONAL_CODE, self::BRANCH_CENTRAL_CODE]
                    ]);

                    $tempQueryTo = [];
                    /** @var Branch $branch */
                    foreach ($branchesTo as $branch) {
                        $tempQueryTo[] = ' entity.toBranch = ' . $branch->getId() . ' ';
                    }

                    $query = ' entity.isReturned = 1 ';
                    if (count($tempQueryTo)) {
                        $query .= ' AND ';
                        $query .= '(';
                        $query .= implode(' OR ', $tempQueryTo);
                        $query .= ')';
                    }

                    $dqlAnd[] = $query;
                }
            } elseif ($this->request->query->has('statusDepartment') && $this->request->query->get('statusDepartment') == 'control') {
                // for control department
                if ($this->request->query->has('packagesType') && $this->request->query->get('packagesType') == 'delivered') {
                    $branchTo = $this->getDoctrine()->getRepository('GCRMCRMBundle:Branch')->findOneBy([
                        'typeCode' => self::BRANCH_OPERATOR_CODE
                    ]);

                    if ($branchTo) {
                        $dqlAnd[] = ' entity.toBranch = ' . $branchTo->getId() . ' ';
                    }
                } elseif ($this->request->query->has('packagesType') && $this->request->query->get('packagesType') == 'returned') {
                    $branchFrom = $this->getDoctrine()->getRepository('GCRMCRMBundle:Branch')->findOneBy([
                        'typeCode' => self::BRANCH_OPERATOR_CODE
                    ]);

                    if ($branchFrom) {
                        $dqlAnd[] = ' entity.fromBranch = ' . $branchFrom->getId() . ' ';
                    }
                }
            }
        }


        // LIST SEARCH MODULE
        if ($this->request->query->has('listSearch')) {
            /** @var ListSearcherStrategyInitializer $listStrategyInitializer */
            $listStrategyInitializer = $this->container->get('gcrm\crmbundle\service\listsearcherstrategyinitializer');
            /** @var EntityListSearcherInterface $chosenObject */
            $chosenObject = $listStrategyInitializer->chooseObjectByEntity($this->entity['class']);
            if ($chosenObject && !$chosenObject instanceof Thread) {
                $statusDepartments = $this->getDoctrine()->getRepository('GCRMCRMBundle:StatusDepartment')->findAll();
                $chosenObject->addQuery($this->request, $dqlAnd, $dqlOr, $statusDepartments);
            } elseif($chosenObject && $chosenObject instanceof Thread) {
                $chosenObject->addQuery($this->request, $dqlAnd, $dqlOr);
            }
        } else {
            if ($this->request->query->get('entity') == 'Client') {
                return $this->redirectToRoute('general');
            }
        }

        if (count($dqlOr) || count($dqlAnd)) {
            if($this->entity['list']['dql_filter']) {
                $this->entity['list']['dql_filter'] .= ' AND ';
            }
        }

        if (count($dqlOr)) {
            $this->entity['list']['dql_filter'] .= count($dqlOr) > 1 ? '(' : '';
            $this->entity['list']['dql_filter'] .= implode(' OR ', $dqlOr);
            $this->entity['list']['dql_filter'] .= count($dqlOr) > 1 ? ')' : '';

        }

        if (count($dqlAnd)) {
            if (count($dqlOr)) {
                $this->entity['list']['dql_filter'] .= ' AND ';
            }
            $this->entity['list']['dql_filter'] .= implode(' AND ', $dqlAnd);
        }
        // END ADDING FILTER AND SELECT TO THE LIST

        $this->dispatch(EasyAdminEvents::PRE_LIST);



        // modify max results
        $session = $this->request->getSession();
        $maxResultsNumber = $session->get('entity_max_results.' . $this->entity['class'] . '.number') ?: $this->entity['list']['max_results'];
        $entityMaxResultsForm = $this->createForm(EntityMaxResultsType::class, [
            'number' => $maxResultsNumber
        ]);
        $entityMaxResultsForm->handleRequest($this->request);
        if ($entityMaxResultsForm->isSubmitted() && $entityMaxResultsForm->isValid()) {
            $number = $entityMaxResultsForm->getData()['number'];
            $session->set('entity_max_results.' . $this->entity['class'] . '.number', $number);
        }
        if ($session->get('entity_max_results.' . $this->entity['class'] . '.number')) {
            $this->entity['list']['max_results'] = (int) $session->get('entity_max_results.' . $this->entity['class'] . '.number');
        }



        $fields = $this->entity['list']['fields'];
        $paginator = $this->findAll($this->entity['class'], $this->request->query->get('page', 1), $this->entity['list']['max_results'], $this->request->query->get('sortField'), $this->request->query->get('sortDirection'), $this->entity['list']['dql_filter']);

        $this->dispatch(EasyAdminEvents::POST_LIST, array('paginator' => $paginator));


        $parameters = array(
            'paginator' => $paginator,
            'fields' => $fields,
            'delete_form_template' => $this->createDeleteForm($this->entity['name'], '__id__')->createView(),
        );
        $parameters['entity_max_results'] = $entityMaxResultsForm->createView();

        // adds form search
        /** @var Request $optionss */
        $options = $this->request->query->all();
        if (isset($options['lsSalesRepresentative']) && is_numeric($options['lsSalesRepresentative'])) {
            $options['lsSalesRepresentative'] = $this->getDoctrine()->getRepository('GCRMCRMBundle:User')->find($options['lsSalesRepresentative']);
        }

        $options['entityClass'] = $this->entity['class'];

        /** @var Form $form */
        $form = $this->createForm(ListSearchType::class, $options);
        $form->handleRequest($this->request);

        // check if generated file exist
        if (
            $this->request->query->get('entity') == 'Invoice' ||
            $this->request->query->get('entity') == 'InvoiceCorrection' ||
            $this->request->query->get('entity') == 'InvoiceProforma'
        ) {
            if ($this->request->query->get('entity') == 'Invoice') {
                $relativePath = self::ROOT_RELATIVE_INVOICES_NEW_VERSION_PATH;
            } elseif ($this->request->query->get('entity') == 'InvoiceCorrection') {
                $relativePath = self::ROOT_RELATIVE_CORRECTIONS_NEW_VERSION_PATH;
            } else {
                $relativePath = self::ROOT_RELATIVE_INVOICES_PROFORMA_PATH;
            }

            $pageResults = $paginator->getCurrentPageResults();
            foreach ($pageResults as $key => $invoice) {
                $filePath = $this->getInvoiceTypeAbsolutePath($invoice, $relativePath);
                if (file_exists($filePath)) {
                    $invoice->setIsGeneratedFileExist(true);
                } else {
                    $invoice->setIsGeneratedFileExist(false);
                }

                $pageResults[$key] = $invoice;
            }

            $parameters['pageResults'] = $pageResults;
        }



        // form list action panel
        $formListAction = $this->createForm(ListActionType::class, $options);
        $formListAction->handleRequest($this->request);
        $parameters['listActionForm'] = $formListAction->createView();

        if ($formListAction->isSubmitted() && $formListAction->isValid()) {
            /** @var ListSearcherStrategyInitializer $listStrategyInitializer */
            $listStrategyInitializer = $this->container->get('gcrm\crmbundle\service\listsearcherstrategyinitializer');

            /** @var ExportHandler $exportHandler */
            $exportHandler = new ExportHandler($this->get('request_stack'), $this->getDoctrine()->getManager(), $this->get('service_container'));

            // BUTTON STATUSES
            $isClickedDownloadXlsx = $formListAction->get('downloadXlsx')->isClicked();
            $isClickedDownloadCSV = $formListAction->get('downloadCsv')->isClicked();



            if ($formListAction->has('downloadXlsxTerminationFormat')) {
                $isClickedDownloadXlsxTerminationFormat = $formListAction->get('downloadXlsxTerminationFormat')->isClicked();
            } else {
                $isClickedDownloadXlsxTerminationFormat = false;
            }

            if ($formListAction->has('downloadCsvTerminationFormat')) {
                $isClickedDownloadCsvTerminationFormat = $formListAction->get('downloadCsvTerminationFormat')->isClicked();
            } else {
                $isClickedDownloadCsvTerminationFormat = false;
            }


            $isClickedDownloadFiles = null;
            if ($formListAction->has('downloadFiles')) {
                $isClickedDownloadFiles = $formListAction->get('downloadFiles')->isClicked();
            }

            // DOWNLOADS
            if ($isClickedDownloadXlsx) {
                $exportHandler->setExporter(new XlsxExporter());
                $output = $exportHandler->export();
                $this->downloadSpreadsheetAsXlsx($output);
            }

            if ($isClickedDownloadCSV) {
                $exportHandler->setExporter(new CsvExporter(';'));
                $output = $exportHandler->export();
                $this->downloadDataAsCSV($output);
            }



            $listsDataProvider = [
//                'downloadXlsxPriceLists' => 'ContractAndPriceLists',
                'downloadCsvPriceLists' => 'ContractAndPriceLists',
//                'downloadXlsxSellerTariffs' => 'ContractAndSellerTariffs',
                'downloadCsvSellerTariffs' => 'ContractAndSellerTariffs',
//                'downloadXlsxDistributionTariffs' => 'ContractAndDistributionTariffs',
                'downloadCsvDistributionTariffs' => 'ContractAndDistributionTariffs',
//                'downloadXlsxPp' => 'ContractAndPp',
                'downloadCsvPp' => 'ContractAndPp',
            ];

            foreach ($listsDataProvider as $key => $value) {
                // client download additional lists
                if ($formListAction->has($key) && $formListAction->get($key)->isClicked()) {
                    if (substr($key, 0, 12) == 'downloadXlsx') {
                        $exportHandler->setExporter(new XlsxExporter());
                        $output = $exportHandler->export($value);
                        $this->downloadSpreadsheetAsXlsx($output);
                    } else {
                        $exportHandler->setExporter(new CsvExporter(';'));
                        $output = $exportHandler->export($value);
                        $this->downloadDataAsCSV($output);
                    }
                }
            }

            if ($isClickedDownloadXlsxTerminationFormat) {
                $exportHandler->setExporter(new XlsxExporter());
                $output = $exportHandler->export('ClientProcessTerminatedTemplate');
                $this->downloadSpreadsheetAsXlsx($output);
            }

            if ($isClickedDownloadCsvTerminationFormat) {
                $exportHandler->setExporter(new CsvExporter(';'));
                $output = $exportHandler->export('ClientProcessTerminatedTemplate');
                $this->downloadDataAsCSV($output);
            }

            if ($formListAction->has('downloadCsvOptima') && $formListAction->get('downloadCsvOptima')->isClicked()) {
                $exporter = null;
                $delimeter = ';';

                if ($this->request->query->get('entity') == 'InvoiceProformaEnergy') {
                    $exporter = 'InvoiceProformaOptimaTemplate';
                } elseif ($this->request->query->get('entity') == 'InvoiceProformaCorrectionEnergy') {
                    $exporter = 'InvoiceProformaCorrectionOptimaTemplate';
                } elseif ($this->request->query->get('entity') == 'InvoiceSettlementEnergy') {
                    $exporter = 'InvoiceSettlementOptimaTemplate';
                } elseif ($this->request->query->get('entity') == 'InvoiceSettlementCorrectionEnergy') {
                    $exporter = 'InvoiceSettlementCorrectionOptimaTemplate';
                } elseif ($this->request->query->get('entity') == 'InvoiceEstimatedSettlementEnergy') {
                    $exporter = 'InvoiceEstimatedSettlementOptimaTemplate';
                } elseif ($this->request->query->get('entity') == 'InvoiceEstimatedSettlementCorrectionEnergy') {
                    $exporter = 'InvoiceEstimatedSettlementCorrectionOptimaTemplate';
                } elseif ($this->request->query->get('entity') == 'Payment') {
                    $exporter = 'PaymentOptimaTemplate';
                    $delimeter = ',';
                } elseif ($this->request->query->get('entity') == 'Invoice') {
                    $exporter = 'InvoiceOptimaTemplate';
                } elseif ($this->request->query->get('entity') == 'InvoiceCorrection') {
                    $exporter = 'InvoiceCorrectionOptimaTemplate';
                } elseif ($this->request->query->get('entity') == 'InvoiceCollective') {
                    $exporter = 'InvoiceCollectiveOptimaTemplate';
                }  elseif ($this->request->query->get('entity') == 'InvoiceEnergy') {
                    $exporter = 'InvoiceEnergyOptimaTemplate';
                }   elseif ($this->request->query->get('entity') == 'InvoiceCorrectionEnergy') {
                    $exporter = 'InvoiceCorrectionEnergyOptimaTemplate';
                }

                if ($exporter) {
                    $exportHandler->setExporter(new CsvExporter($delimeter));
                    $output = $exportHandler->export($exporter);
                    $this->downloadDataAsCSV($output);
                }
            }

            if ($formListAction->has('downloadXlsContractorsOptima') && $formListAction->get('downloadXlsContractorsOptima')->isClicked()) {
                $exportHandler->setExporter(new XlsxExporter());
                $output = $exportHandler->export('ContractorsOptimaTemplate');
                $this->downloadSpreadsheetAsXls($output);
            }

            if ($isClickedDownloadFiles) {
                 $allResults = $this->getAllPaginatorResults($paginator);
                 $filesPathsToDownload = $chosenObject->getFilesToDownload($this->get('kernel')->getRootDir(), $allResults, $this->entity['class']);

                 $filesToDownload = [];
                 $filesNotExists = [];

                 foreach ($filesPathsToDownload as $path) {
                     if (file_exists($path)) {
                         $filesToDownload[] = $path;
                     } else {
                         $filesNotExists[] = $path;
                     }
                 }

                 if ($filesToDownload) {
                     $this->zipModel->download($filesToDownload, 'Files');
                     die;
                 } else {
                     if ($filesNotExists) {
                         $this->addFlash('notice', 'Nie znaleziono plików dla: ' . implode(',', $filesNotExists));
                     } else {
                         $this->addFlash('notice', 'Nie znaleziono żadnych rekordów.');
                     }
                     return $this->redirectToReferrer();
                 }
            }
        }

        /** @var ListSearcherStrategyInitializer $listStrategyInitializer */
        $listStrategyInitializer = $this->container->get('gcrm\crmbundle\service\listsearcherstrategyinitializer');
        /** @var ListDataExporterInterface $chosenObject */
        $chosenObject = $listStrategyInitializer->chooseObjectByEntity($this->entity['class']);
        
        $parameters['listSearchForm'] = null;
        if ($chosenObject) {
            $parameters['listSearchForm'] = $form->createView();
        }

        if ($this->request->query->get('entity') == 'Client') {
            $currentPage = $paginator->getCurrentPage();
            $maxPerPage = $paginator->getMaxPerPage();

            // get ALL results
            $allDataCount = [];//$allResults['contractsDataCount'];

            // get CURRENT PAGE results
            $paginator->setCurrentPage($currentPage);
            $paginator->setMaxPerPage($maxPerPage);
            $pageResults = $paginator->getCurrentPageResults();

            $parameters['contractsByType'] = $this->getContractsByTypeFromClients($pageResults);
            $parameters['contractsByType']['contractsDataCount'] = $allDataCount;
            $parameters['contractsByType']['contractsDataCountSummary'] = $this->contractsDataCountSummary($allDataCount);
            $parameters['pageResults'] = $pageResults;
        }


        $contractTypes = $this->getDoctrine()->getRepository('GCRMCRMBundle:ContractType')->findAll();
        $parameters['contractTypes'] = $contractTypes;


        /** @var ListSearcherStrategyInitializer $listStrategyInitializer */
        $listStrategyInitializer = $this->container->get('gcrm\crmbundle\service\listsearcherstrategyinitializer');
        /** @var EntityListSearcherInterface $chosenObject */
        $chosenObject = $listStrategyInitializer->chooseObjectByEntity($this->entity['class']);
        $parameters['listSearcher']['twigTemplate'] = null;
        if ($chosenObject) {
            $parameters['listSearcher']['twigTemplate'] = $chosenObject->getTwigTemplate();
        }





        // Apply DocumentBankAccountChange functionality to list view
        if ($this->request->query->get('entity') == 'DocumentBankAccountChange') {
            $formDocumentBankAccountChange = $this->createForm(FileUploadType::class);
            $formDocumentBankAccountChange->handleRequest($this->request);
            $parameters['formDocumentBankAccountChange'] = $formDocumentBankAccountChange->createView();

            $formButtonClick = $this->createForm(ButtonClickType::class);
            $formButtonClick->handleRequest($this->request);
            $parameters['formButtonClick'] = $formButtonClick->createView();
        }





        $available = [
            'InvoiceProformaEnergy'
        ];
        if (in_array($this->request->query->get('entity'), $available)) {
            // mass correction panel
            $massCorrectionForm = $this->createForm(MassCorrectionType::class);
            $parameters['massCorrectionForm'] = $massCorrectionForm->createView();
            $massCorrectionForm->handleRequest($this->request);
            $entityId = $this->documentModel->getMappedIdByOption($this->request->query->get('entity'));
            $correctionEntity = $this->documentModel->getCorrectionEntity($this->request->query->get('entity'));
            $correctionEntityId = $this->documentModel->getMappedIdByOption($correctionEntity);

            if ($this->request->request->get('multiRollBack')) {
                $selectedRows = $this->request->request->get('selectedRowsPackages');

                if ($selectedRows) {
                    foreach ($selectedRows as $selectedRowId) {
                        /** @var DocumentPackageToGenerate $documentPackageToGenerate */
                        $documentPackageToGenerate = $this->documentPackageToGenerateModel->getRecord($selectedRowId);
                        if (!$documentPackageToGenerate) {
                            continue;
                        }

                        $this->documentPackageToGenerateModel->deleteRecord($documentPackageToGenerate);
                    }
                }
            }

            if ($this->request->request->get('mass_correction') && $this->request->request->get('selectedRows')) {
                $selectedRows = $this->request->request->get('selectedRows');
                $massCorrectionParams = $this->request->request->get('mass_correction');

                $objects = $this->em->getRepository($this->entity['class'])->findBy(['id' => $selectedRows]);
                if ($objects) {
                    $this->documentPackageToGenerateModel->createPackage(
                        $objects,
                        $this->getUser(),
                        $entityId,
                        $correctionEntityId,
                        DocumentPackageToGenerateModel::TYPE_CORRECTION,
                        $massCorrectionParams,
                        new \DateTime()
                    );

                    return $this->redirectToRoute('easyadmin', array(
                        'action' => 'list',
                        'entity' => $this->request->query->get('entity'),
                    ));
                }
            }

            if ($this->request->request->get('changeStatusToProcessAction')) {
                $id = $this->request->request->get('changeStatusToProcessAction');
                /** @var DocumentPackageToGenerate $packageRecord */
                $packageRecord = $this->documentPackageToGenerateModel->getRecord($id);
                if ($packageRecord) {
                    $packageRecord->setStatus(DocumentPackageToGenerateModel::STATUS_IN_PROCESS);
                    $packageRecord->setErrorMessage(null);
                    $this->em->persist($packageRecord);
                    $this->em->flush();
                }

                return $this->redirectToRoute('easyadmin', array(
                    'action' => 'list',
                    'entity' => $this->request->query->get('entity'),
                ));
            }

            if ($this->request->files->get('mass_correction')['file']) {
                $massCorrectionParams = $this->request->request->get('mass_correction');
                $fullPathToFile = $this->uploadedFileHelper->createTmpFile($this->request->files->get('mass_correction')['file'], 'tmp-mass-custom-document');
                $rows = $this->spreadsheetReader->fetchRows('Xlsx', $fullPathToFile, 1, 'B');

                if ($rows) {
                    $documentNumbers = [];
                    foreach ($rows as $row) {
                        $documentNumbers[] = $row[1];
                    }

                    $objects = $this->em->getRepository($this->entity['class'])->findBy(['number' => $documentNumbers]);
                    if ($objects) {
                        $this->documentPackageToGenerateModel->createPackage(
                            $objects,
                            $this->getUser(),
                            $entityId,
                            $correctionEntityId,
                            DocumentPackageToGenerateModel::TYPE_CORRECTION,
                            $massCorrectionParams,
                            new \DateTime()
                        );

                        $this->addFlash('success', 'Utworzono paczkę.');

                        return $this->redirectToRoute('easyadmin', array(
                            'action' => 'list',
                            'entity' => $this->request->query->get('entity'),
                        ));
                    }
                }
            }

            // packages
            $parameters['packagesToGenerate'] = $this->documentPackageToGenerateModel->getRecords($entityId);
        }

        $available = [
            'CustomDocumentTemplate'
        ];
        if (in_array($this->request->query->get('entity'), $available)) {
            // mass correction panel
            $massCustomDocumentForm = $this->createForm(MassCustomDocumentType::class);
            $parameters['massCustomDocumentForm'] = $massCustomDocumentForm->createView();
            $massCustomDocumentForm->handleRequest($this->request);
            $entityId = $this->documentModel->getMappedIdByOption($this->request->query->get('entity'));
            if (
                $this->request->request->get('mass_custom_document') &&
                $this->request->request->get('mass_custom_document')['customDocumentTemplate'] &&
                $this->request->files->get('mass_custom_document')['file']
            ) {
                /** @var CustomDocumentTemplate $customDocumentTemplate */
                $customDocumentTemplate = $this->customDocumentTemplateModel->getRecord(
                    $this->request->request->get('mass_custom_document')['customDocumentTemplate']
                );

                $fullPathToFile = $this->uploadedFileHelper->createTmpFile($this->request->files->get('mass_custom_document')['file'], 'tmp-mass-custom-document');
                $rows = $this->spreadsheetReader->fetchRows('Xlsx', $fullPathToFile, 1, 'A');

                $clients = [];
                foreach ($rows as $row) {
                    $client = $this->clientModel->getClientByBadgeId($row[0]);
                    if (!$client) {
                        continue;
                    }
                    $clients[] = $client;
                }

                if ($clients) {
                    $massCustomDocumentParams = $this->request->request->get('mass_custom_document');
                    try {
                        $this->documentPackageToGenerateModel->createPackageForCustomDocuments(
                            $customDocumentTemplate,
                            $clients,
                            $this->getUser(),
                            $entityId,
                            $massCustomDocumentParams,
                            new \DateTime()
                        );

                        $this->addFlash('success', 'Utworzono paczkę.');
                    } catch (\Exception $e) {
                        $this->addFlash('error', $e->getMessage());
                    }
                } else {
                    $this->addFlash('notice', 'Nie znaleziono klientów. Sprawdź wgrywany plik i spróbuj ponownie.');
                }
                return $this->redirectToRoute('easyadmin', array(
                    'action' => 'list',
                    'entity' => $this->request->query->get('entity'),
                ));
            }

            if ($this->request->request->get('changeStatusToProcessAction')) {
                $id = $this->request->request->get('changeStatusToProcessAction');
                /** @var DocumentPackageToGenerate $packageRecord */
                $packageRecord = $this->documentPackageToGenerateModel->getRecord($id);
                if ($packageRecord) {
                    $packageRecord->setStatus(DocumentPackageToGenerateModel::STATUS_IN_PROCESS);
                    $packageRecord->setErrorMessage(null);
                    $this->em->persist($packageRecord);
                    $this->em->flush();
                }

                return $this->redirectToRoute('easyadmin', array(
                    'action' => 'list',
                    'entity' => $this->request->query->get('entity'),
                ));
            }

            // packages
            $parameters['packagesToGenerate'] = $this->documentPackageToGenerateModel->getRecords($entityId);
        }

        $available = [
            'CustomDocumentTemplate',
            'InvoiceProformaEnergy'
        ];
        if (in_array($this->request->query->get('entity'), $available)) {
            if ($this->request->request->get('generateEnveloAction')) {
                /** @var DocumentPackageToGenerate $package */
                $package = $this->documentPackageToGenerateModel->getRecord($this->request->request->get('generateEnveloAction'));
                if ($package) {
                    $documents = [];
                    /** @var DocumentPackageToGenerateRecord $packageRecord */
                    foreach ($package->getPackageRecords() as $packageRecord) {
                        if ($packageRecord->getStatus() != DocumentPackageToGenerateRecordModel::STATUS_COMPLETE) {
                            continue;
                        }

                        $documents[] = $packageRecord;
                    }

                    if (count($documents)) {
                        // todo
                        $documentRelativeDir = null;
                        if ($package->getGeneratedDocumentEntity()) {
                            $documentRelativeDir = $this->easyAdminModel->getEntityDirectoryRelativeByEntityName($this->documentModel->getMappedOptionByValue($package->getGeneratedDocumentEntity()));
                        }

                        $this->enveloModel->generateForDocumentsPackageToGenerate(
                            $package,
                            $package->getId(),
                            $documents,
                            $documentRelativeDir
                        );
                    }
                }
            }
        }

        if ($this->request->request->get('multiDeleteAction')) {
            $selectedRows = $this->request->request->get('selectedRows');
            $objects = $this->em->getRepository($this->entity['class'])->findBy(['id' => $selectedRows]);
            if ($objects) {
                foreach ($objects as $object) {
                    $this->em->remove($object);
                    $this->em->flush();
                }

                $this->addFlash('success', 'Rekordy zostały usunięte.');

                return $this->redirectToRoute('easyadmin', array(
                    'action' => 'list',
                    'entity' => $this->request->query->get('entity'),
                ));
            }
        }

        return $this->executeDynamicMethod('render<EntityName>Template', array('list', $this->entity['templates']['list'], $parameters));
    }

    private function getAllPaginatorResults($paginator)
    {
        $paginatorTemp = clone $paginator;
        $paginatorTemp->setCurrentPage(1);
        $paginatorTemp->setMaxPerPage($paginatorTemp->getNbResults() ? $paginatorTemp->getNbResults() : 1);
        return $paginatorTemp->getCurrentPageResults();
    }

    private function contractsDataCountSummary($dataByContractType)
    {
        $result = 0;

        foreach ($dataByContractType as $count) {
            $result = $result + $count;
        }

        return $result;
    }

    private function getContractsByTypeFromClients(&$clients)
    {
        $contractTypes = $this->getDoctrine()->getRepository('GCRMCRMBundle:ContractType')->findAll();

        $result = ['contractsDataCount' => []];

        /** @var \GCRM\CRMBundle\Entity\ContractType $contractType */
        foreach ($contractTypes as $contractType) {
            $result['contractsDataCount'][mb_strtolower($contractType->getCode())] = 0;
        }

        /** @var Client $client */
        foreach ($clients as $client) {
            $client->contractsList = [];
            $this->modifiedContractsByType($client->getClientAndGasContracts(), 'GAZ', 'gas', $client, $result);
            $this->modifiedContractsByType($client->getClientAndEnergyContracts(), 'PRĄD', 'energy', $client, $result);
        }

        return $result;
    }

    private function modifiedContractsByType($contractHolders, $typeTitle, $typeCode, &$client, &$result)
    {
        if ($this->request->query->has('listSearch')) {
            foreach ($contractHolders as $contractHolder) {
                /** @var ContractInterface $contract */
                $contract = $contractHolder->getContract();
                if (!$contract) {
                    continue;
                }

                /** @var ListSearcherStrategyInitializer $listStrategyInitializer */
                $listStrategyInitializer = $this->container->get('gcrm\crmbundle\service\listsearcherstrategyinitializer');
                /** @var EntityListSearcherInterface $chosenObject */
                $chosenObject = $listStrategyInitializer->chooseObjectByEntity($this->entity['class']);
                if (!$chosenObject) {
                    continue;
                }

                $contract = $chosenObject->applyFilterListSearch($this->request, $contract);
                if (!$contract) {
                    continue;
                }

                $contract->typeTitle = $typeTitle;
                $contract->typeCode = $typeCode;

                $client->contractsList[] = $contract;
                if (isset($result['contractsDataCount'][$typeCode])) {
                    $result['contractsDataCount'][$typeCode]++;
                }
            }
        } else {
            foreach ($contractHolders as $contractHolder) {
                /** @var ContractInterface $contract */
                $contract = $contractHolder->getContract();
                if (!$contract) {
                    continue;
                }

                if (
//                    !$contract->getIsDownloaded() && !$contract->getIsOnPackageList() &&
                    ($this->request->get('statusDepartment') && $contract->getStatusDepartment() && $contract->getStatusDepartment()->getCode() == $this->request->get('statusDepartment') || $this->request->get('statusDepartment') == null)
                ) {
                    $contract->typeTitle = $typeTitle;
                    $contract->typeCode = $typeCode;
                    $client->contractsList[] = $contract;
                    if (isset($result['contractsDataCount'][$typeCode])) {
                        $result['contractsDataCount'][$typeCode]++;
                    }
                }
            }
        }
    }

    /**
     * @Route("/downloadPackageToSend/{id}", name="downloadPackageToSend")
     */
    public function downloadPackageToSendAction(PackageToSendModel $packageToSendModel, $id)
    {
        /** @var PackageToSend $packageToSend */
        $packageToSend = $this->getDoctrine()->getRepository('GCRMCRMBundle:PackageToSend')->find($id);

        if (!$packageToSend) {
            throw new NotFoundHttpException();
        }

        $packageToSendModel->generateDocument($packageToSend, $this->getUser());
    }

//    private function downloadPackageAsCsv($package, EntityManager $em)
//    {
//        $entity = null;
//        if ($package->getContractType() == 'gas') {
//            $entity = 'GCRMCRMBundle:ContractGas';
//        } elseif ($package->getContractType() == 'energy') {
//            $entity = 'GCRMCRMBundle:ContractEnergy';
//        }
//
//        if (!$entity) {
//            throw new NotFoundHttpException();
//        }
//
//        $qb = $em->createQueryBuilder();
//        $q = $qb->select(['a'])
//            ->from($entity, 'a')
//            ->where('a.id IN (:ids)')
//            ->setParameters([
//                'ids' => explode(',', $package->getContractIds())
//            ])
//            ->getQuery()
//        ;
//
//        $contracts = $q->getResult();
//
//        $spreadsheet = null;
//        if ($package->getContractType() == 'gas') {
//            $spreadsheet = new Spreadsheet();
//            $spreadsheet = $this->updateContractGasSpreadsheet($spreadsheet, $contracts, false, $em);
//        } elseif ($package->getContractType() == 'energy') {
//            $spreadsheet = new Spreadsheet();
//            $spreadsheet = $this->updateContractEnergySpreadsheet($spreadsheet, $contracts, false, $em);
//        }
//
//        if ($spreadsheet) {
//            $this->downloadSpreadsheetAsXlsx($spreadsheet);
//        } else {
//            $this->addFlash('notice', 'Brak rekordów');
//        }
//    }

    /**
     * {@inheritdoc}
     */
    protected function findAll($entityClass, $page = 1, $maxPerPage = 15, $sortField = null, $sortDirection = null, $dqlFilter = null)
    {
        if (empty($sortDirection) || !in_array(strtoupper($sortDirection), array('ASC', 'DESC'))) {
            $sortDirection = 'DESC';
        }

        $queryBuilder = $this->executeDynamicMethod('create<EntityName>ListQueryBuilder', array($entityClass, $sortDirection, $sortField, $dqlFilter));

        /** @var ListSearcherStrategyInitializer $listStrategyInitializer */
        $listStrategyInitializer = $this->container->get('gcrm\crmbundle\service\listsearcherstrategyinitializer');
        /** @var EntityListSearcherInterface $chosenObject */
        $chosenObject = $listStrategyInitializer->chooseObjectByEntity($entityClass);
        if ($chosenObject) {
            $chosenObject->fromTables($queryBuilder);
            $chosenObject->joinTables($queryBuilder);

            if ($this->request->query->has('listSearch')) {
                $chosenObject->addParameters($queryBuilder, $this->request);
            }
        }

        $this->dispatch(EasyAdminEvents::POST_LIST_QUERY_BUILDER, array(
            'query_builder' => $queryBuilder,
            'sort_field' => $sortField,
            'sort_direction' => $sortDirection,
        ));

        return $this->get('easyadmin.paginator')->createOrmPaginator($queryBuilder, $page, $maxPerPage);
    }

    /**
     * @Route("/client-back-to-administration", name="clientBackToAdministration")
     */
    public function clientBackToAdministration(Request $request, EntityManager $em)
    {
        $id = $request->query->get('id');

        /** @var Client $client */
        $client = $em->getRepository('GCRMCRMBundle:Client')->find($id);

        if (!$client) {
            throw new NotFoundHttpException();
        }

        $administrationStatusDepartment = $em->getRepository('GCRMCRMBundle:StatusDepartment')->findOneBy([
            'code' => 'administration'
        ]);

        if (!$administrationStatusDepartment) {
            throw new NotFoundHttpException();
        }

        $statusNew = $em->getRepository('GCRMCRMBundle:StatusClient')->findOneBy([
            'code' => 'new'
        ]);

        if (!$statusNew) {
            throw new NotFoundHttpException();
        }

        $client->setStatusDepartment($administrationStatusDepartment);
        $client->setStatus($statusNew);
        $client->setAddDepartmentComment('REKORD ZOSTAŁ COFNIĘTY PRZEZ ' . $this->getUser());
        $client->setCheckUser(null);
        $client->setCheckTime(null);
        $em->persist($client);
        $em->flush();

        // redirect to the 'edit' view of the given entity item
        return $this->redirectToRoute('easyadmin', array(
            'action' => 'edit',
            'id' => $id,
            'entity' => $request->query->get('entity'),
        ));
    }

    /**
     * @Route("/contracts-to-package-list", name="contractsToPackageList")
     */
    public function contractsToPackageList(Request $request, EntityManager $em, UserModel $userModel)
    {
        $contractTypeCode = $request->query->get('contractType');

        if ($contractTypeCode == 'gas') {
            $entity = 'GCRMCRMBundle:ContractGas';
            $entityClass = 'ContractGas';
            $entityClientAndContract = 'GCRMCRMBundle:ClientAndContractGas';
        } elseif ($contractTypeCode == 'energy') {
            $entity = 'GCRMCRMBundle:ContractEnergy';
            $entityClass = 'ContractEnergy';
            $entityClientAndContract = 'GCRMCRMBundle:ClientAndContractEnergy';
        } else {
            throw new NotFoundHttpException();
        }


        $qb = $em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from($entity, 'a')
            ->where('a.isOnPackageList = :true')
            ->andWhere('a.packageToSend IS NULL')
            ->setParameters([
                'true' => true,
            ])
            ->getQuery()
        ;

        $contracts = $q->getResult();

        $clientsAndContracts = $this->getDoctrine()->getRepository($entityClientAndContract)->findBy([
            'contract' => $contracts
        ]);

        $userBranchesIndexedById = $userModel->getBranchesIndexedById($this->getUser());

        // filter only those records from current user branch and records that dont have sales representative and / or branch
        // filter got sense only if current user have branch
        if ($this->getUser()->getBranch()) {
            $filteredClientsAndContracts = [];
            foreach ($clientsAndContracts as $clientAndContract) {
                $contract = $clientAndContract->getContract();
                $salesRepresentative = $contract->getSalesRepresentative();
                if (!$salesRepresentative || ($salesRepresentative && !$salesRepresentative->getBranch())) {
                    $filteredClientsAndContracts[] = $clientAndContract;
                    continue;
                } elseif ($salesRepresentative && $salesRepresentative->getBranch()) {
                    /** @var Branch $branch */
                    $branch = $salesRepresentative->getBranch();
                    if (array_key_exists($branch->getId(), $userBranchesIndexedById)) {
                        $filteredClientsAndContracts[] = $clientAndContract;
                        continue;
                    }
                }
            }
            if (count($filteredClientsAndContracts)) {
                $clientsAndContracts = $filteredClientsAndContracts;
            }
        }

        return $this->render('@GCRMCRM/Default/contracts-to-process-list.html.twig', [
            'clientsAndContracts' => $clientsAndContracts,
            'entityClass' => $entityClass,
            'contractTypeCode' => $contractTypeCode,
        ]);
    }

    /**
     * @Route("/contracts-packages", name="contractsPackages")
     */
    public function contractsPackages(Request $request)
    {
        $contractTypeCode = $request->query->get('contractType');

        if ($contractTypeCode == 'gas') {
            $entity = 'GCRMCRMBundle:ContractGas';
            $entityClass = 'ContractGas';
            $entityClientAndContract = 'GCRMCRMBundle:ClientAndContractGas';
        } elseif ($contractTypeCode == 'energy') {
            $entity = 'GCRMCRMBundle:ContractEnergy';
            $entityClass = 'ContractEnergy';
            $entityClientAndContract = 'GCRMCRMBundle:ClientAndContractEnergy';
        } else {
            throw new NotFoundHttpException();
        }

        $contracts = $this->getDoctrine()->getRepository($entity)->findBy([
            'isOnPackageList' => true
        ]);

        $clientsAndContracts = $this->getDoctrine()->getRepository($entityClientAndContract)->findBy([
            'contract' => $contracts
        ]);

        $branches = $this->getDoctrine()->getRepository('GCRMCRMBundle:Branch')->findAll();

        return $this->render('@GCRMCRM/Default/contracts-to-process-list.html.twig', [
            'clientsAndContracts' => $clientsAndContracts,
            'entityClass' => $entityClass,
            'contractTypeCode' => $contractTypeCode,
            'branches' => $branches
        ]);
    }

    /**
     * @Route("/contract-back-to-administration", name="contractBackToAdministration")
     */
    public function contractBackToAdministration(Request $request, EntityManager $em)
    {
        $id = $request->query->get('id');

        /** @var Contract $contract */
        $contract = $em->getRepository('GCRMCRMBundle:Contract')->find($id);

        if (!$contract) {
            throw new NotFoundHttpException();
        }

        $administrationStatusDepartment = $em->getRepository('GCRMCRMBundle:StatusDepartment')->findOneBy([
            'code' => 'administration'
        ]);

        if (!$administrationStatusDepartment) {
            throw new NotFoundHttpException();
        }

        $statusNew = $em->getRepository('GCRMCRMBundle:StatusContract')->findOneBy([
            'code' => 'new'
        ]);

        if (!$statusNew) {
            throw new NotFoundHttpException();
        }

        $contract->setStatusDepartment($administrationStatusDepartment);
        $contract->setStatus($statusNew);
        $contract->setAddDepartmentComment('REKORD ZOSTAŁ COFNIĘTY PRZEZ ' . $this->getUser());
        $contract->setCheckUser(null);
        $contract->setCheckTime(null);
        $em->persist($contract);
        $em->flush();

        // redirect to the 'edit' view of the given entity item
        return $this->redirectToRoute('easyadmin', array(
            'action' => 'edit',
            'id' => $id,
            'entity' => $request->query->get('entity'),
        ));
    }

    /**
     * @Route("/register-me-as-check-user-client", name="registerMeAsCheckUserClient")
     */
    public function registerMeAsCheckUserClient(Request $request, EntityManager $em)
    {
        $id = $request->query->get('id');

        /** @var Client $client */
        $client = $em->getRepository('GCRMCRMBundle:Client')->find($id);

        if (!$client) {
            throw new NotFoundHttpException();
        }

        if ($client->getCheckUser() && $client->getCheckUser() != $this->getUser()) {
            $this->addFlash('notice', 'Rekord jest aktualnie sprawdzany przez inną osobę.');
        } else {
            $now = new \DateTime();
            $newTime = $now->modify('+1 hour');
            $client->setCheckUser($this->getUser());
            $client->setCheckTime($newTime);
            $em->persist($client);
            $em->flush();
        }

        return $this->redirectToRoute('easyadmin', array(
            'action' => 'list',
            'id' => $id,
            'entity' => $request->query->get('entity'),
        ));
    }

    /**
     * @Route("/register-me-as-check-user-contract", name="registerMeAsCheckUserContract")
     */
    public function registerMeAsCheckUserContract(Request $request, EntityManager $em)
    {
        $id = $request->query->get('id');

        /** @var Contract $contract */
        $contract = $em->getRepository('GCRMCRMBundle:Contract')->find($id);

        if (!$contract) {
            throw new NotFoundHttpException();
        }

        if ($contract->getCheckUser() && $contract->getCheckUser() != $this->getUser()) {
            $this->addFlash('notice', 'Rekord jest aktualnie sprawdzany przez inną osobę.');
        } else {
            $now = new \DateTime();
            $newTime = $now->modify('+1 hour');
            $contract->setCheckUser($this->getUser());
            $contract->setCheckTime($newTime);
            $em->persist($contract);
            $em->flush();
        }

        return $this->redirectToRoute('easyadmin', array(
            'action' => 'list',
            'id' => $id,
            'entity' => $request->query->get('entity'),
        ));
    }

    private function checkPermissions()
    {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $easyAdmin = $this->request->attributes->get('easyadmin');
        $action = $this->request->query->get('action');
        $userRoles = $this->getUser()->getRoles();

        // Entire entity page view permissions
        $permissions = isset($easyAdmin['entity']['permissions']) ? $easyAdmin['entity']['permissions'] : [];
        foreach ($permissions as $permission) {
            if (!in_array($permission, $userRoles)) {
                throw $this->createAccessDeniedException();
            }
        }

        // Actions entity permissions [list, edit, new, delete]
        $permissions = isset($easyAdmin['entity'][$action]['permissions']) ? $easyAdmin['entity'][$action]['permissions'] : [];
        foreach ($permissions as $permission) {
            if (!in_array($permission, $userRoles)) {
                throw $this->createAccessDeniedException();
            }
        }
    }

    /**
     * @Route("/", name="easyadmin")
     * @Route("/", name="admin")
     *
     * The 'admin' route is deprecated since version 1.8.0 and it will be removed in 2.0.
     *
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function indexAction(Request $request)
    {
        $this->initialize($request);

        if (null === $request->query->get('entity')) {
            return $this->redirectToBackendHomepage();
        }

        $action = $request->query->get('action', 'list');
        if (!$this->isActionAllowed($action)) {
            throw new ForbiddenActionException(array('action' => $action, 'entity_name' => $this->entity['name']));
        }
        $this->checkPermissions();

        return $this->executeDynamicMethod($action . '<EntityName>Action');
    }

    /**
     * @Route("/package-to-process/{id}", name="showPackageToProcess")
     */
    public function showPackageToProcessAction(Request $request, EntityManager $em, $id)
    {
        /** @var PackageToProcess $packageToProcess */
        $packageToProcess = $em->getRepository('GCRMCRMBundle:PackageToProcess')->find($id);

        if (!$packageToProcess) {
            throw new NotFoundHttpException();
        }

        $entity = null;
        $entityClientAndContract = null;
        if ($packageToProcess->getContractType() == 'gas') {
            $entity = 'GCRMCRMBundle:ContractGas';
            $entityClientAndContract = 'GCRMCRMBundle:ClientAndContractGas';
        } elseif ($packageToProcess->getContractType() == 'energy') {
            $entity = 'GCRMCRMBundle:ContractEnergy';
            $entityClientAndContract = 'GCRMCRMBundle:ClientAndContractEnergy';
        }

        if (!$entity) {
            throw new NotFoundHttpException();
        }

        $qb = $em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from($entity, 'a')
            ->where('a.id IN (:ids)')
            ->setParameters([
                'ids' => explode(',', $packageToProcess->getContractIds())
            ])
            ->getQuery()
        ;

        $contracts = $q->getResult();

        $clientsAndContracts = $this->getDoctrine()->getRepository($entityClientAndContract)->findBy([
            'contract' => $contracts
        ]);

        return $this->render('@GCRMCRM/Default/package-to-process.html.twig', [
            'packageToProcess' => $packageToProcess,
            'clientsAndContracts' => $clientsAndContracts
        ]);
    }

    /**
     * @Route("/reset-negative-contract-state/{id}", name="resetNegativeContractState")
     */
    public function resetNegativeContractState(Request $request, EntityManager $em, EasyAdminModel $easyAdminModel, ValidateRoleAccess $validateRoleAccess, $id)
    {
        try {
            $validateRoleAccess->validateAccess('ROLE_SUPERADMIN', $this->getUser());
        } catch (AccessRestrictedException $e) {
            $this->addFlash('error', 'Brak uprawnień do wykonania tej czynności.');
            return $this->redirectToRoute('easyadmin', array(
                'action' => 'edit',
                'id' => $id,
                'entity' => $request->query->get('entity'),
            ));
        }

        $entity = $request->query->get('entity');
        if (!$entity) {
            throw new NotFoundHttpException();
        }

        if (!$id) {
            throw new NotFoundHttpException();
        }

        $class = $easyAdminModel->getEntityClassByEntityName($entity);
        if (!$class) {
            throw new NotFoundHttpException();
        }

        $contract = $em->getRepository($class)->find($id);
        if (!$contract) {
            throw new NotFoundHttpException();
        }

        if (!method_exists($contract, 'getIsBrokenContract') || !method_exists($contract, 'getIsResignation')) {
            throw new NotFoundHttpException();
        }
        if (!$contract->getIsBrokenContract() && !$contract->getIsResignation()) {
            $this->addFlash('notice', 'Brak wykonanych akcji - aktualnie umowa jest w obiegu.');
        } else {
            $contract->setIsBrokenContract(false);
            $contract->setIsResignation(false);
            $em->persist($contract);
            $em->flush();

            $this->addFlash('success', 'Umowa została przywrócona do obiegu.');
        }

        return $this->redirectToRoute('easyadmin', array(
            'action' => 'edit',
            'id' => $id,
            'entity' => $request->query->get('entity'),
        ));
    }

    /**
     * @Route("/package-to-send/{id}", name="showPackageToSend")
     */
    public function showPackageToSendAction(Request $request, EntityManager $em, PackageToSendModel $packageToSendModel, StatusContractModel $statusContractModel, $id)
    {
        /** @var PackageToSend $packageToSend */
        $packageToSend = $em->getRepository('GCRMCRMBundle:PackageToSend')->find($id);

        if (!$packageToSend) {
            throw new NotFoundHttpException();
        }

        if (!$packageToSend->getOriginBranch()) {
            die('Paczka nie ma przypisanego oddziału początkowego. Przypisz oddział i spróbuj ponownie.');
        }

        $entity = null;
        $entityClientAndContract = null;
        if ($packageToSend->getContractType() == 'gas') {
            $entity = 'GCRMCRMBundle:ContractGas';
            $entityClientAndContract = 'GCRMCRMBundle:ClientAndContractGas';
        } elseif ($packageToSend->getContractType() == 'energy') {
            $entity = 'GCRMCRMBundle:ContractEnergy';
            $entityClientAndContract = 'GCRMCRMBundle:ClientAndContractEnergy';
        }

        if (!$entity) {
            throw new NotFoundHttpException();
        }

        $qb = $em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from($entity, 'a')
            ->where('a.id IN (:ids)')
            ->setParameters([
                'ids' => explode(',', $packageToSend->getContractIds())
            ])
            ->getQuery()
        ;

        $contracts = $q->getResult();

        $clientsAndContracts = $this->getDoctrine()->getRepository($entityClientAndContract)->findBy([
            'contract' => $contracts
        ]);


        $packagesType = $request->query->get('packagesType');
        $statusDepartment = $request->query->get('statusDepartment');
        $isSelectingActionEnabled = false;


        $isForm = false;
        if (
            $packagesType &&
            $statusDepartment &&
            (
                ($statusDepartment == 'administration' && ($packagesType == 'delivered' || $packagesType == 'returned')) ||
                ($statusDepartment == 'control' && ($packagesType == 'delivered'))
            )
        ) {
            $isForm = true;
        }


        $isButtonAcceptReturn = false;
        if ($packagesType && $statusDepartment && $statusDepartment == 'administration' && $packagesType == 'returned') {
            $isButtonAcceptReturn = true;
        }

        $isButtonControlProcessPackage = false;
        if ($packagesType && $statusDepartment && ($statusDepartment == 'control' && $packagesType == 'delivered') || ($statusDepartment == 'administration' && $packagesType == 'delivered')) {
            $isButtonControlProcessPackage = true;
        }

        $isButtonAdministrationProcessPackage = false;
        if ($packagesType && $statusDepartment && $statusDepartment == 'administration' && ($packagesType == 'delivered' || ($packagesType == 'returned' && $packageToSend->getToBranch()->getTypeCode() == 'BC' && $packageToSend->getOriginBranch()->getTypeCode() != 'BC')) ) {
            $isButtonAdministrationProcessPackage = true;
        }


        // process packages to send
        // ADMINISTRATION DEPARTMENT
        $processPackage = $request->request->get('administrationProcessPackage');
        if ($processPackage) {
            $user = $this->getUser();
            /** @var Branch $userBranch */
            $userBranch = $user->getBranch();
            if (!$userBranch) {
                die('Nie można procesować paczki - nie masz przypisanego oddziału');
            }

            if ($userBranch->getId() != $packageToSend->getToBranch()->getId()) {
                die('Nie można procesować paczki - nie jesteś przypisany/a do tego oddziału.');
            }

            $statusAdministrationDepartment = $em->getRepository('GCRMCRMBundle:StatusDepartment')->findOneBy(['code' => 'administration']);
            if (!$statusAdministrationDepartment) {
                die('Departament administracji nie został odnaleziony. Skontaktuj się z administratorem.');
            }

            $isAlreadyProcessed = $packageToSend->getIsProcessed();
            if ($isAlreadyProcessed) {
                $this->addFlash('error', 'Paczka była już procesowana.');
                return $this->redirect($request->getRequestUri());
            }

            $selectedRowsGood = $request->get('selectedRowsGood');
            $selectedRowsBad = $request->get('selectedRowsBad');

            if (is_array($selectedRowsGood) && is_array($selectedRowsBad)) {
                if (count(array_intersect($selectedRowsGood, $selectedRowsBad))) {
                    $this->addFlash('error', 'Niektóre umowy są oznaczone jako dobre i błędne jednocześnie.');
                    return $this->redirect($request->getRequestUri());
                }
            }

            $contractsSelectedBad = [];
            $contractsSelectedBadIds = [];
            $contractsSelectedGood = [];
            $contractsSelectedGoodIds = [];

            foreach ($clientsAndContracts as $clientAndContract) {
                $contract = $clientAndContract->getContract();

                // if contract belongs to another package where its active, ommit this contract
                $contractActualPackageToSend = $contract->getPackageToSend();
                if ($packageToSend != $contractActualPackageToSend) {
                    continue;
                }

                if ($selectedRowsBad && in_array($contract->getId(), $selectedRowsBad)) {
                    $contractsSelectedBad[] = $contract;
                    $contractsSelectedBadIds[] = $contract->getId();
                } elseif ($selectedRowsGood && in_array($contract->getId(), $selectedRowsGood)) {
                    $contractsSelectedGood[] = $contract;
                    $contractsSelectedGoodIds[] = $contract->getId();
                } else {
                    $this->addFlash('error', 'Niektóre umowy nie są jeszcze oznaczone.');
                    return $this->redirect($request->getRequestUri());
                }
            }

            // to return
            $em->getConnection()->beginTransaction();
            try {
                if (count($contractsSelectedBadIds)) {
                    $newPackageToSend = new PackageToSend();
                    $newPackageToSend->setContractIds($contractsSelectedBadIds);
                    $newPackageToSend->setNumber($packageToSendModel->generateNumber($packageToSend->getOriginBranch()->getTypeCode(), $packageToSend->getContractType()));
                    $newPackageToSend->setAddedBy($this->getUser());
                    $newPackageToSend->setFromBranch($packageToSend->getToBranch());
                    $newPackageToSend->setToBranch($packageToSend->getOriginBranch());
                    $newPackageToSend->setOriginBranch($packageToSend->getOriginBranch());
                    $newPackageToSend->setContractType($packageToSend->getContractType());
                    $newPackageToSend->setIsReturned(true);
                    $newPackageToSend->setIsProcessed(false);

                    $em->persist($newPackageToSend);

                    foreach ($contractsSelectedBad as $contract) {
                        $contract->setPackageToSend($newPackageToSend);
                        $contract->setIsReturned(true);
                        $em->persist($contract);
                    }

                    $em->flush();
                }

                if (count($contractsSelectedGood)) {
                    $newPackageToSendGood = new PackageToSend();
                    $newPackageToSendGood->setContractIds($contractsSelectedGoodIds);
                    $branchToSendGood = $this->getDoctrine()->getRepository('GCRMCRMBundle:branch')->findOneBy(['typeCode' => 'BO']);
                    $newPackageToSendGood->setNumber($packageToSendModel->generateNumber('BO', $packageToSend->getContractType()));
                    $newPackageToSendGood->setAddedBy($this->getUser());
                    $newPackageToSendGood->setFromBranch($packageToSend->getToBranch());
                    $newPackageToSendGood->setToBranch($branchToSendGood);
                    $newPackageToSendGood->setContractType($packageToSend->getContractType());
                    $newPackageToSendGood->setOriginBranch($packageToSend->getOriginBranch());
                    $newPackageToSendGood->setIsReturned(false);
                    $newPackageToSendGood->setIsProcessed(false);

                    $em->persist($newPackageToSendGood);

                    foreach ($contractsSelectedGood as $contract) {
                        $contract->setPackageToSend($newPackageToSendGood);
                        $em->persist($contract);
                    }

                    $em->flush();
                }

                $packageToSend->setIsProcessed(true);
                $packageToSend->setCheckedIdsGood($contractsSelectedGoodIds);
                $packageToSend->setCheckedIdsBad($contractsSelectedBadIds);
                $em->flush();

                $em->getConnection()->commit();

                $this->addFlash('success', 'Akcja procesowania zakończona pomyślnie.');
                return $this->redirect($request->getRequestUri());
            } catch (\Exception $e) {
                $em->getConnection()->rollBack();
            }
        }

        $saveChangesGoodBadPackage = $request->request->get('saveChangesGoodBadPackage');
        if ($saveChangesGoodBadPackage) {
            $user = $this->getUser();
            /** @var Branch $userBranch */
            $userBranch = $user->getBranch();
            if (!$userBranch) {
                die('Nie można zapisać zmian - nie masz przypisanego oddziału');
            }

            if ($userBranch->getId() != $packageToSend->getToBranch()->getId()) {
                die('Nie można zapisać zmian - nie jesteś przypisany/a do tego oddziału.');
            }

            $selectedRowsGood = $request->get('selectedRowsGood');
            $selectedRowsBad = $request->get('selectedRowsBad');

            $isAlreadyProcessed = $packageToSend->getIsProcessed();
            if ($isAlreadyProcessed) {
                $this->addFlash('error', 'Paczka była już procesowana.');
                return $this->redirect($request->getRequestUri());
            }

            $packageToSend->setCheckedIdsGood($selectedRowsGood);
            $packageToSend->setCheckedIdsBad($selectedRowsBad);
            $em->persist($packageToSend);
            $em->flush();

            $this->addFlash('success', 'Zmiany zostały zapisane.');
            return $this->redirect($request->getRequestUri());
        }

        $controlProcessPackage = $request->request->get('controlProcessPackage');
        if ($controlProcessPackage) {
            $statusControlDepartment = $em->getRepository('GCRMCRMBundle:StatusDepartment')->findOneBy(['code' => 'control']);
            if (!$statusControlDepartment) {
                die('Departament kontroli nie został odnaleziony. Skontaktuj się z administratorem.');
            }

            $isAlreadyProcessed = $packageToSend->getIsProcessed();
            if ($isAlreadyProcessed) {
                $this->addFlash('error', 'Paczka była już procesowana.');
                return $this->redirect($request->getRequestUri());
            }

            $selectedRowsGood = $request->get('selectedRowsGood');
            $selectedRowsBad = $request->get('selectedRowsBad');

            if (is_array($selectedRowsGood) && is_array($selectedRowsBad)) {
                if (count(array_intersect($selectedRowsGood, $selectedRowsBad))) {
                    $this->addFlash('error', 'Niektóre umowy są oznaczone jako dobre i błędne jednocześnie.');
                    return $this->redirect($request->getRequestUri());
                }
            }

            $contractsSelectedBad = [];
            $contractsSelectedBadIds = [];
            $contractsSelectedGood = [];
            $contractsSelectedGoodIds = [];

            foreach ($clientsAndContracts as $clientAndContract) {
                $contract = $clientAndContract->getContract();

                // if contract belongs to another package where its active, ommit this contract
                $contractActualPackageToSend = $contract->getPackageToSend();
                if ($packageToSend != $contractActualPackageToSend) {
                    continue;
                }

                if ($selectedRowsBad && in_array($contract->getId(), $selectedRowsBad)) {
                    $contractsSelectedBad[] = $contract;
                    $contractsSelectedBadIds[] = $contract->getId();
                } elseif ($selectedRowsGood && in_array($contract->getId(), $selectedRowsGood)) {
                    $contractsSelectedGood[] = $contract;
                    $contractsSelectedGoodIds[] = $contract->getId();
                } else {
                    $this->addFlash('error', 'Niektóre umowy nie są jeszcze oznaczone.');
                    return $this->redirect($request->getRequestUri());
                }
            }

            // to return
            $em->getConnection()->beginTransaction();
            try {
                if (count($contractsSelectedBadIds)) {
                    $newPackageToSend = new PackageToSend();
                    $newPackageToSend->setContractIds($contractsSelectedBadIds);
                    $newPackageToSend->setNumber($packageToSendModel->generateNumber($packageToSend->getFromBranch()->getTypeCode(), $packageToSend->getContractType()));
                    $newPackageToSend->setAddedBy($this->getUser());
                    $newPackageToSend->setFromBranch($packageToSend->getToBranch());
                    $newPackageToSend->setToBranch($packageToSend->getFromBranch());
                    $newPackageToSend->setOriginBranch($packageToSend->getOriginBranch());
                    $newPackageToSend->setContractType($packageToSend->getContractType());
                    $newPackageToSend->setIsReturned(true);
                    $newPackageToSend->setIsProcessed(false);

                    $em->persist($newPackageToSend);

                    foreach ($contractsSelectedBad as $contract) {
                        $contract->setPackageToSend($newPackageToSend);
                        $contract->setIsReturned(true);
                        $em->persist($contract);
                    }

                    $em->flush();
                }

                if (count($contractsSelectedGood)) {
                    foreach ($contractsSelectedGood as $contract) {
                        $contract->setStatusDepartment($statusControlDepartment);
                        $contract->setPackageToSend(null);
                        $contract->setIsOnPackageList(false);
                        $em->persist($contract);
                    }
                    $em->flush();
                }

                $packageToSend->setIsProcessed(true);
                $packageToSend->setCheckedIdsGood($contractsSelectedGoodIds);
                $packageToSend->setCheckedIdsBad($contractsSelectedBadIds);
                $em->flush();

                $em->getConnection()->commit();

                $this->addFlash('success', 'Akcja procesowania zakończona pomyślnie.');
                return $this->redirect($request->getRequestUri());
            } catch (\Exception $e) {
                $em->getConnection()->rollBack();
            }
        }

        $acceptReturn = $request->request->get('acceptReturn');
        if ($acceptReturn) {
            foreach ($clientsAndContracts as $clientAndContract) {
                $contract = $clientAndContract->getContract();

                // if contract belongs to another package where its active, ommit this contract
                $contractActualPackageToSend = $contract->getPackageToSend();
                if ($packageToSend != $contractActualPackageToSend) {
                    continue;
                }

                $contract->setPackageToSend(null);
                $contract->setIsOnPackageList(false);
                if (!$contract->getIsResignation() && !$contract->getIsBrokenContract()) {
                    // get status contract - to fix
                    $statusesContractToFix = $statusContractModel->getStatusContractsBySpecialActionOption(StatusContractModel::SPECIAL_ACTION_SET_THIS_STATUS_AFTER_RETURN);
                    if ($statusesContractToFix && count($statusesContractToFix)) {
                        $contract->setStatusContractAdministration($statusesContractToFix[0]);
                    } else {
                        $contract->setStatusContractAdministration(null);
                    }
                }

                $em->persist($contract);
                $em->flush();
            }

            $packageToSend->setIsProcessed(true);
            $em->flush();
        }

        $contractsNotActualNumber = 0;
        if ($clientsAndContracts) {
            foreach ($clientsAndContracts as $clientsAndContract) {
                $contract = $clientsAndContract->getContract();
                if (!$contract) {
                    continue;
                }

                if ($contract->getIsResignation() || $contract->getIsBrokenContract()) {
                    $contractsNotActualNumber++;
                }
            }
        }

        return $this->render('@GCRMCRM/Default/package-to-send.html.twig', [
            'packageToSend' => $packageToSend,
            'clientsAndContracts' => $clientsAndContracts,
            'contractsNotActualNumber' => $contractsNotActualNumber,
            'isForm' => $isForm,
            'isButtonAcceptReturn' => $isButtonAcceptReturn,
            'isButtonControlProcessPackage' => $isButtonControlProcessPackage,
            'isButtonAdministrationProcessPackage' => $isButtonAdministrationProcessPackage
        ]);
    }

    /**
     * @Route("/cancelPackageToProcess", name="cancelPackageToProcess")
     */
    public function cancelPackageToProcessAction(Request $request, EntityManager $em)
    {
        $id = $request->query->get('id');

        /** @var PackageToProcess $packageToProcess */
        $packageToProcess = $em->getRepository('GCRMCRMBundle:PackageToProcess')->find($id);

        if (!$packageToProcess) {
            throw new NotFoundHttpException();
        }

        if ($packageToProcess->getIsCancelled()) {
            $this->addFlash('notice', 'Paczka została już wcześniej anulowana');
        } else {
            $entity = null;
            if ($packageToProcess->getContractType() == 'gas') {
                $entity = 'GCRMCRMBundle:ContractGas';
            } elseif ($packageToProcess->getContractType() == 'energy') {
                $entity = 'GCRMCRMBundle:ContractEnergy';
            }

            if (!$entity) {
                throw new NotFoundHttpException();
            }

            $qb = $em->createQueryBuilder();
            $q = $qb->select(['a'])
                ->from($entity, 'a')
                ->where('a.id IN (:ids)')
                ->setParameters([
                    'ids' => explode(',', $packageToProcess->getContractIds())
                ])
                ->getQuery()
            ;

            $contracts = $q->getResult();

            foreach ($contracts as $contract) {
                $contract->setIsDownloaded(false);
                $em->persist($contract);
            }

            $packageToProcess->setIsCancelled(true);
            $packageToProcess->setCancelledBy($this->getUser());

            $em->persist($packageToProcess);
            $em->flush();
        }

        return $this->redirectToRoute('easyadmin', array(
            'action' => 'list',
            'id' => $id,
            'entity' => $request->query->get('entity'),
        ));
    }

    /**
     * @Route("/packagesListPostAction", name="packagesListPostAction")
     */
    public function packagesListPostAction(Request $request, EntityManager $em, PackageToSendModel $packageToSendModel)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $entityClass = $request->query->get('entityClass');
        $contractType = $request->query->get('contractType');
        $selectedRows = $request->get('selectedRows');

        $selectedContractsIds = [];

        if ($selectedRows) {
            $multiUnlinkAction = $request->request->get('multiUnlinkAction');
            if ($multiUnlinkAction) {
                foreach ($selectedRows as $rowId) {
                    $row = $this->getDoctrine()->getRepository('GCRMCRMBundle:' . $entityClass)->find($rowId);
                    if ($row) {
                        $row->setIsOnPackageList(false);
                        $em->persist($row);
                        $em->flush();
                    }
                }
            }


            $multiMakePackage = $request->request->get('multiMakePackage');

            if ($multiMakePackage) {
                /** @var User $user */
                $user = $this->getUser();
                /** @var Branch $branch */
                $userBranch = $user->getBranch();
                if (!$userBranch) {
                    $this->addFlash('error', 'Nie możesz utworzyć paczki, ponieważ nie jesteś przypisany/a do żadnego oddziału.');
                    return $this->redirectToRoute('contractsToPackageList', $request->query->all());
                }

                $sendToCode = null;
                if ($userBranch->getTypeCode() == 'BR') {
                    $sendToCode = 'BC';
                } elseif ($userBranch->getTypeCode() == 'BC') {
                    $sendToCode = 'BO';
                } else {
                    die('Oddział do którego jesteś przypisany/a nie pozwala na tworzenie paczek w tym miejscu.');
                }

                /** @var Branch $branch */
                $branch = $this->getDoctrine()->getRepository('GCRMCRMBundle:branch')->findOneBy(['typeCode' => $sendToCode]);
                if (!$branch) {
                    $this->addFlash('error', 'Wybrany oddział nie istnieje.');
                    $this->redirectToRoute('contractsToPackageList', $request->query->all());
                }


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
                        $packageToSend = new PackageToSend();
                        $packageToSend->setContractIds($selectedContractsIds);
                        $packageToSend->setNumber($packageToSendModel->generateNumber($branch->getTypeCode(), $contractType));
                        $packageToSend->setAddedBy($this->getUser());
                        $packageToSend->setFromBranch($user->getBranch());
                        $packageToSend->setToBranch($branch);
                        $packageToSend->setContractType($contractType);
                        $packageToSend->setOriginBranch($userBranch);
                        $packageToSend->setIsProcessed(false);

                        $em->persist($packageToSend);

                        foreach ($contracts as $contract) {
                            $contract->setPackageToSend($packageToSend);
                            $em->persist($contract);
                        }

                        $em->flush();

                        $em->getConnection()->commit();

                        $this->addFlash('success', 'Paczka została utworzona.');
                    } catch (\Exception $e) {
                        $em->getConnection()->rollBack();
                        $this->addFlash('error', 'Wystąpił błąd. Spróbuj ponownie.');
                    }
                }
            }
        }

        return $this->redirectToRoute('contractsToPackageList', $request->query->all());
    }


    /**
     * @Route("/listPostAction", name="listPostAction")
     */
    public function listPostAction(Request $request)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $entityClass = $request->request->get('entityClass');
        $selectedRows = $request->get('selectedRows');

        if ($selectedRows) {
            $multiDeleteAction = $request->request->get('multiDeleteAction');
            if ($multiDeleteAction) {
                foreach ($selectedRows as $rowId) {
                    $row = $this->getDoctrine()->getRepository($entityClass)->find($rowId);

                    if ($row) {
                        $em->remove($row);
                        $em->flush();
                    }
                }
            }

            $multiCloneAction = $request->request->get('multiCloneAction');
            if ($multiCloneAction) {
                foreach ($selectedRows as $rowId) {
                    $row = $this->getDoctrine()->getRepository($entityClass)->find($rowId);

                    if ($row) {
                        $newRow = clone $row;
                        $em->persist($newRow);
                        $em->flush();
                    }
                }
            }
        }

        return $this->redirectToRoute('easyadmin', $request->query->all());
    }

    private function getInvoiceTypeAbsolutePath(InvoiceInterface $invoice, $relativePath)
    {
        $invoiceDate = $invoice->getCreatedDate();
        $datePieces = explode('-', $invoiceDate->format('Y-m-d'));

        $number = $invoice->getNumber();
        $invoiceFilename = str_replace('/', '-', $number);

        $invoicesPath = $this->get('kernel')->getRootDir() . '/../' . $relativePath;

        $fullInvoicePath = $invoicesPath . $datePieces[0] . '/' . $datePieces[1] . '/' . $invoiceFilename;
        $fullInvoicePathWithExtension = $fullInvoicePath . '.pdf';

        if (file_exists($fullInvoicePathWithExtension)) {
            $fullInvoicePath = $fullInvoicePathWithExtension;
        }

        return $fullInvoicePath;
    }


    private function downloadDataAsCSV($output)
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="dane.csv"');
        header('Cache-Control: max-age=0');

        file_put_contents('php://output', $output);
        exit;
    }

    private function downloadSpreadsheetAsXlsx($spreadsheet)
    {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="dane.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    private function downloadSpreadsheetAsXls($spreadsheet)
    {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="dane.xls"');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($spreadsheet, 'Xls');
        $writer->save('php://output');
        exit;
    }

    private function formatDate($datetime)
    {
        if (!is_object($datetime)) {
            return null;
        }

        return $datetime->format('d-m-Y');
    }

    protected function getDataRows($file, $firstDataRowIndex, $highestColumn)
    {
        $reader = new Xlsx();
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

    protected function getDataRowsUniversal($reader, $file, $firstDataRowIndex, $highestColumn)
    {
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

    /**
     * @Route("/get-file-data-payments-old", name="getFileDataPaymentsOld")
     */
    public function getFileDataPaymentsOldEnrexAction(Request $request, EntityManager $em, InvoiceModel $invoiceModel)
    {
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $payments = $em->getRepository('GCRMCRMBundle:PaymentOldEnrex')->findAll();

        // group by badgeId
        /** @var PaymentOldEnrex $payment */
        $paymentsGroupedByBadgeId = [];
        foreach ($payments as $payment) {
            if (!key_exists($payment->getBadgeId(), $paymentsGroupedByBadgeId)) {
                $paymentsGroupedByBadgeId[$payment->getBadgeId()] = [];
            }
            $paymentsGroupedByBadgeId[$payment->getBadgeId()][] = $payment;
        }

        $spreadsheet = new Spreadsheet();

        $spreadsheet->getActiveSheet()->setCellValue('A1', 'Lp.');
        $spreadsheet->getActiveSheet()->setCellValue('B1', 'Nr rachunku');
        $spreadsheet->getActiveSheet()->setCellValue('C1', 'Kwota wpłaty');
        $spreadsheet->getActiveSheet()->setCellValue('D1', 'Data wpłaty');
        $spreadsheet->getActiveSheet()->setCellValue('E1', 'Suma');

        /** @var Client $client */
        $index = 2;
        foreach ($paymentsGroupedByBadgeId as $badgeId => $paymentsGrouped) {

            $summary = 0;
            /** @var PaymentOldEnrex $payment */
            foreach ($paymentsGrouped as $payment) {
                $summary += $payment->getValue();
            }

            /** @var PaymentOldEnrex $payment */
            foreach ($paymentsGrouped as $payment) {
                $spreadsheet->getActiveSheet()->setCellValue('A' . $index, $index - 1);
                $spreadsheet->getActiveSheet()->setCellValue('B' . $index, $payment->getBadgeId());
                $spreadsheet->getActiveSheet()->setCellValue('C' . $index, $payment->getValue());
                $spreadsheet->getActiveSheet()->setCellValue('D' . $index, $payment->getDate()->format('Y-m-d'));
                $spreadsheet->getActiveSheet()->setCellValue('E' . $index, $summary);

                $index++;
            }
        }

        if ($spreadsheet) {
            $this->downloadSpreadsheetAsXlsx($spreadsheet);
        } else {
            $this->addFlash('notice', 'Brak rekordów');
        }
    }

    /**
     * @Route("/client-page/{id}", name="clientPage")
     */
    public function clientPageAction(Request $request, EntityManager $em, ValidateRoleAccess $validateRoleAccess, PaymentModel $paymentModel, ClientModel $clientModel, CompanyModel $companyModel, InvoiceModel $invoiceModel, \Wecoders\InvoiceBundle\Service\InvoiceModel $invoiceBundleInvoiceModel, EasyAdminModel $easyAdminModel, Initializer $initializer, PaymentRequestModel $paymentRequestModel, $id)
    {
        $validateRoleAccess->validateAccess('ROLE_CLIENT_PAGE', $this->getUser());

        // if there is a list view ex. invoices,
        // button "Karta klienta" have id of that record and not client id
        $fetchClientFrom = $request->query->get('fetchClientFromEntity');
        $fetchFromClass = null;
        if ($fetchClientFrom) {
            $fetchFromClass = $easyAdminModel->getEntityClassByEntityName($fetchClientFrom);
        }

        if ($fetchFromClass) {
            $object = $this->getDoctrine()->getRepository($fetchFromClass)->find($id);
            if (!$object) {
                return $this->redirectToRoute('general');
            }

            $byBadgeId = $request->query->get('byBadgeId');
            $client = null;
            if ($byBadgeId) {
                $badgeId = $object->getBadgeId();
                if ($badgeId) {
                    $client = $clientModel->getClientByBadgeId($badgeId);
                }
            } else {
                $client = $object->getClient();
            }

            if (!$client) {
                return $this->redirectToRoute('general');
            }
        } else {
            /** @var Client $client */
            $client = $this->getDoctrine()->getRepository('GCRMCRMBundle:Client')->find($id);
            if (!$client) {
                return $this->redirectToRoute('general');
            }
        }
        //


        // contracts access
        $contractsAccess = $validateRoleAccess->checkIfHaveAccess(['ROLE_CONTRACTS'], $this->getUser());
        $contracts = [
            'GAZ' => null,
            'PRĄD' => null,
        ];
        if ($contractsAccess) {
            $contracts['GAZ'] = $this->contractsByType($client->getClientAndGasContracts(), 'gas');
            $contracts['PRĄD'] = $this->contractsByType($client->getClientAndEnergyContracts(), 'energy');
        }


        // Add contract
        $formAddContract = $this->createForm(AddContractType::class);
        $formAddContract->handleRequest($request);

        if ($formAddContract->isSubmitted() && $formAddContract->isValid()) {
            $contractTypes = $formAddContract->get('contractTypes')->getData();
            if (count($contractTypes)) {

                $statusContractAction = $this->getDoctrine()->getRepository('GCRMCRMBundle:StatusContractAction')->findOneBy([
                    'code' => 'GO'
                ]);
                if (!$statusContractAction) {
                    die('Wybrany status rozmowy nie istnieje. Skontaktuj się z administratorem.');
                }

                $statusAuthorization = $this->getDoctrine()->getRepository('GCRMCRMBundle:StatusContractAuthorization')->findOneBy([
                    'statusContractAction' => $statusContractAction
                ]);
                if (!$statusAuthorization) {
                    die('Wybrany status rozmowy nie istnieje. Skontaktuj się z administratorem.');
                }

                $statusDepartment = $this->getDoctrine()->getRepository('GCRMCRMBundle:StatusDepartment')->findOneBy([
                    'code' => 'verification'
                ]);
                if (!$statusDepartment) {
                    die('Status dla departamentu weryfikacji nie został jeszcze zdefiniowany');
                }


                $loopIndex = 0;
                /** @var \GCRM\CRMBundle\Entity\ContractType $contractType */
                foreach ($contractTypes as $contractType) {
                    if ($contractType->getCode() == 'gas') {
                        $contract = new ContractGas();
                        $contract->setType('GAS');
                        $clientAndContractType = new ClientAndContractGas();
                    } elseif ($contractType->getCode() == 'energy') {
                        $contract = new ContractEnergy();
                        $contract->setType('ENERGY');
                        $clientAndContractType = new ClientAndContractEnergy();
                    } else {
                        continue;
                    }

                    $contract->setContractNumber('nieprzypisany-' . $loopIndex . '-' . $this->getUser()->getId() . time());
                    $contract->setUser($this->getUser());
                    $contract->setStatusDepartment($statusDepartment);
                    $contract->setStatusAuthorization($statusAuthorization);
                    $contract->setCommentAuthorization(null);
                    $contract->setIsDownloaded(false);
                    $contract->setIsOnPackageList(false);
                    $contract->setIsReturned(false);
                    $contract->setIsResignation(false);
                    $contract->setIsBrokenContract(false);

                    $em->persist($contract);

                    $clientAndContractType->setClient($client);
                    $clientAndContractType->setContract($contract);

                    $em->persist($clientAndContractType);

                    $loopIndex++;
                }

                $em->flush();
                $this->addFlash('success', 'Dodano umowy');

                return $this->redirectToRoute('clientPage', ['id' => $client->getId()]);
            }
        }

        $documentsStructure = $initializer->init($client)->generate()->getStructure();

        $paymentRequests = $paymentRequestModel->getRecordsByClient($client);

        $threadsAndClient = $this->getDoctrine()->getRepository('TZiebura\CorrespondenceBundle\Entity\ThreadAndClient')->findBy(['client' => $client]);
        $threads = [];

        foreach($threadsAndClient as $threadAndClient) {
            $tmp = $threadAndClient->getThread();
            if(!$tmp) {
                continue;
            }

            $threads[] = $tmp;
        }

        return $this->render('@GCRMCRM/Default/client-page.html.twig', [
            'client' => $client,
            'contracts' => $contracts,
            'threads' => isset($threads) ? $threads : array(),
            'formAddContract' => $formAddContract->createView(),
            'documentsStructure' => $documentsStructure,
            'paymentRequests' => $paymentRequests,
        ]);
    }

    public function removePackageAdminEntity($entity)
    {
        if($entity->getIsProcessed()) {
            $this->addFlash('error', 'Nie można usunąć odebranej paczki');
        } else {
            $this->em->getConnection()->beginTransaction();
            try {
                $contractIds = explode(',', $entity->getContractIds());
                switch($entity->getContractType()) {
                    case 'gas':
                        $entityClass = 'GCRM\CRMBundle\Entity\ContractGas';
                        break;
                    case 'energy':
                        $entityClass = 'GCRM\CRMBundle\Entity\ContractEnergy';
                        break;
                    default:
                        throw new \Exception();
                }
                foreach($contractIds as $id) {
                    $contract = $this->em->getRepository($entityClass)->find(trim($id));
                    $contract
                        ->setIsOnPackageList(false)
                        ->setPackageToSend(null)
                    ;
                    $this->em->flush($contract);
                }
                $this->em->remove($entity);
                $this->em->flush();
                $this->em->commit();
            } catch(\Exception $e) {
                $this->em->rollBack();
                $this->addFlash('Nie udało się usunąć paczki, wystąpił błąd przy usuwaniu umów z paczki');
            }
        }
        return $this->redirectToRoute('easyadmin', array(
            'action' => 'list',
            'entity' => $this->request->query->get('entity'),
        ));
    }

    private function contractsByType($clientAndContracts, $type)
    {
        $result = [];
        /** @var ClientAndContractGas $clientAndGasContract */
        foreach ($clientAndContracts as $clientAndContract) {
            $contract = $clientAndContract->getContract();
            if (!$contract) {
                continue;
            }

            $contract->editLinkByStatusDepartment = $this->generateContractEditLinkByStatusDepartmentAndType($contract, $type);
            $result[] = $clientAndContract->getContract();
        }

        return $result;
    }

    private function generateContractEditLinkByStatusDepartmentAndType($contract, $type)
    {
        if (!is_object($contract) || !$type) {
            return null;
        }

        /** @var StatusDepartment $statusDepartment */
        $statusDepartment = $contract->getStatusDepartment();
        if (!$statusDepartment) {
            return null;
        }

        return '/admin/?entity=Contract' . ucfirst(mb_strtolower($type)) . ucfirst(mb_strtolower($statusDepartment->getCode())) . 'Department&action=edit&id=' . $contract->getId();
    }

    /**
     * @Route("/search-client-page", name="searchClientPage")
     */
    public function searchClientPageAction(Request $request, ValidateRoleAccess $validateRoleAccess)
    {
        $uri = $request->get('uri');
        if (!$uri) {
            throw new NotFoundHttpException();
        }

        $clientAccess = $validateRoleAccess->checkIfHaveAccess(['ROLE_CLIENTS'], $this->getUser());
        if (!$clientAccess) {
            throw new AccessRestrictedException();
        }

        $data = $request->get('clientPageSearch');

        /** @var Client $clientByPesel */
        $clientByPesel = $this->getDoctrine()->getRepository('GCRMCRMBundle:Client')->findOneBy([
            'pesel' => $data
        ]);

        if ($clientByPesel) {
            return $this->redirectToRoute('clientPage', ['id' => $clientByPesel->getId()]);
        }

        return $this->redirect($uri);
    }

    /**
     * @Route("/administration-statistics", name="administrationStatistics")
     */
    public function administrationStatisticsAction(Request $request, EntityManager $em, ValidateRoleAccess $validateRoleAccess)
    {
        $validateRoleAccess->validateAccess(self::ROLE_ADMINISTRATION_STATISTICS, $this->getUser());

        $statisticsModel = new StatisticsModel($em);
        $clients = [];
        $salesRepresentatives = [];
        $dateStatsResult = null;


        $statsOptionIds = [
            100, // Added clients
            200, // Added contracts
        ];
        $choices = $statisticsModel->getOptionsArrayByIds($statsOptionIds);

        $formStats = $this->createForm(StatisticsType::class, $choices);
        $formStats->handleRequest($request);

        if ($formStats->isSubmitted() && $formStats->isValid()) {
            $dateFrom = $formStats->get('dateFrom')->getData();
            $dateTo = $formStats->get('dateTo')->getData();
            $option = $formStats->get('options')->getData();

            $dateStatsResult = $statisticsModel->getDateFromToValue($dateFrom, $dateTo, $option);
        }

        $chartData['currentMonth'] = $statisticsModel->chartDataCurrentMonth();
        $chartData['monthBack'] = $statisticsModel->chartDataNumberOfDaysBack([
            [
                'title' => 'Wprowadzone umowy',
                'color' => 'red',
                'entity' => ['GCRMCRMBundle:Contract' => 'contracts'],
            ],
            [
                'title' => 'Wprowadzeni klienci',
                'color' => 'blue',
                'entity' => ['GCRMCRMBundle:Client' => 'clients'],
            ],
        ], 30);

        $clients['summaryCount'] = $statisticsModel->getClientsCount();
        $clients['dailyCount'] = $statisticsModel->getDailyClientsCount();

        $contracts['summaryCount'] = $statisticsModel->getContractsCount();
        $contracts['dailyCount'] = $statisticsModel->getDailyContractsCount();

        $salesRepresentatives['summaryCount'] = $statisticsModel->getSalesRepresentativesCount();

        return $this->render('@GCRMCRM/Default/administration-statistics.html.twig', [
            'dateStatsResult' => $dateStatsResult,
            'formStats' => $formStats->createView(),
            'chartData' => $chartData,
            'clients' => $clients,
            'contracts' => $contracts,
            'salesRepresentatives' => $salesRepresentatives,
        ]);
    }

    /**
     * @Route("/finances-statistics", name="financesStatistics")
     */
    public function financesStatisticsAction(Request $request, EntityManager $em, ValidateRoleAccess $validateRoleAccess)
    {
        $validateRoleAccess->validateAccess(self::ROLE_FINANCES_STATISTICS, $this->getUser());

        $statisticsModel = new StatisticsModel($em);
        $dateStatsResult = null;
        $invoices = [];


        $statsOptionIds = [
            1, // Invoices gross value
            2, // Invoices net value
        ];
        $choices = $statisticsModel->getOptionsArrayByIds($statsOptionIds);

        $formStats = $this->createForm(StatisticsType::class, $choices);
        $formStats->handleRequest($request);

        if ($formStats->isSubmitted() && $formStats->isValid()) {
            $dateFrom = $formStats->get('dateFrom')->getData();
            $dateTo = $formStats->get('dateTo')->getData();
            $option = $formStats->get('options')->getData();

            $dateStatsResult = $statisticsModel->getDateFromToValue($dateFrom, $dateTo, $option);
        }

        $chartData['currentMonth'] = $statisticsModel->chartDataCurrentMonth();
        $chartData['monthBack'] = $statisticsModel->chartDataNumberOfDaysBack([
            [
                'title' => 'Wystawione faktury',
                'color' => 'green',
                'entity' => ['GCRMCRMBundle:Invoices' => 'invoices'],
            ],
        ], 30);

        $invoices['summaryCount'] = $statisticsModel->getInvoicesCount();
        $invoices['dailyCount'] = $statisticsModel->getDailyInvoicesCount();

        return $this->render('@GCRMCRM/Default/finances-statistics.html.twig', [
            'dateStatsResult' => $dateStatsResult,
            'formStats' => $formStats->createView(),
            'chartData' => $chartData,
            'invoices' => $invoices,
        ]);
    }

    public function createNewUserEntity()
    {
        return $this->get('fos_user.user_manager')->createUser();
    }

    public function prePersistUserEntity($user)
    {
        $this->get('fos_user.user_manager')->updateUser($user, false);
    }

    public function preUpdateUserEntity($user)
    {
        $this->get('fos_user.user_manager')->updateUser($user, false);
    }

    /**
     * @Route("/vich-display/{dir}/{url}", name="vichDisplayPrivate")
     */
    public function vichDisplayPrivateAction(Request $request, $dir, $url)
    {
        $fullPath = $this->getParameter('vich.path.absolute.private') . '/' . $dir . '/' . $url;

        header('Content-type: ' . mime_content_type($fullPath));
        echo readfile($fullPath);
        die;
    }

    /**
     * @Route("/generate-invoice-action-new-version", name="generateInvoiceNewVersion")
     */
    public function generateInvoiceNewVersionAction(Request $request, EntityManager $em, InvoiceModel $invoiceModel)
    {
        $id = $request->query->get('id');
        /** @var InvoiceInterface $invoice */
        $invoice = $em->getRepository('GCRMCRMBundle:Invoice')->find($id);

        /** @var Client $client */
        $client = $invoice->getClient();
        if (!$client) {
            die('Nie znaleziono klienta na podstawie numeru faktury. Sprawdź czy klient istnieje w bazie oraz czy ma przypisany indywidualny numer.');
        }

        $this->generateInvoiceNewVersionFileFromInvoice($this->getFullInvoiceNewVersionPath($invoice, self::ROOT_RELATIVE_INVOICES_NEW_VERSION_PATH), $invoice);

        // redirect to the 'edit' view of the given entity item
        return $this->redirectToRoute('easyadmin', array(
            'action' => 'edit',
            'id' => $id,
            'entity' => $request->query->get('entity'),
        ));
    }

    /**
     * @Route("/generate-invoice-proforma-action", name="generateInvoiceProforma")
     */
    public function generateInvoiceProformaAction(Request $request, EntityManager $em, InvoiceModel $invoiceModel)
    {
        $id = $request->query->get('id');
        /** @var InvoiceInterface $invoice */
        $invoice = $em->getRepository('GCRMCRMBundle:InvoiceProforma')->find($id);

        /** @var Client $client */
        $client = $invoiceModel->getClientByInvoiceNumber($invoice->getNumber(), $invoice->getNumberStructure(), 'GCRMCRMBundle:Client', 'badgeId');
        if (!$client) {
            die('Nie znaleziono klienta na podstawie numeru faktury. Sprawdź czy klient istnieje w bazie oraz czy ma przypisany indywidualny numer.');
        }

        $this->generateInvoiceProformaFileFromInvoice($this->getFullInvoiceNewVersionPath($invoice, self::ROOT_RELATIVE_INVOICES_PROFORMA_PATH), $invoice);

        // redirect to the 'edit' view of the given entity item
        return $this->redirectToRoute('easyadmin', array(
            'action' => 'edit',
            'id' => $id,
            'entity' => $request->query->get('entity'),
        ));
    }

    /**
     * @Route("/generate-correction-action-new-version", name="generateCorrectionNewVersion")
     */
    public function generateCorrectionNewVersionAction(Request $request, EntityManager $em)
    {
        $id = $request->query->get('id');
        /** @var InvoiceInterface $invoice */
        $invoiceCorrection = $em->getRepository('GCRMCRMBundle:InvoiceCorrection')->find($id);

        $this->generateCorrectionNewVersionFileFromInvoice($this->getFullInvoiceNewVersionPath($invoiceCorrection, self::ROOT_RELATIVE_CORRECTIONS_NEW_VERSION_PATH), $invoiceCorrection);

        // redirect to the 'edit' view of the given entity item
        return $this->redirectToRoute('easyadmin', array(
            'action' => 'edit',
            'id' => $id,
            'entity' => $request->query->get('entity'),
        ));
    }

    private function getFullInvoiceNewVersionPath(InvoiceInterface $invoice, $relativeDirectoryPath)
    {
        /** @var \DateTime $invoiceDate */
        $invoiceDate = $invoice->getCreatedDate();
        $datePieces = explode('-', $invoiceDate->format('Y-m-d'));

        $invoicesPath = $this->get('kernel')->getRootDir() . '/../' . $relativeDirectoryPath;

        $fullPath = $invoicesPath . '/' . $datePieces[0] . '/' . $datePieces[1];

        if (!file_exists($fullPath)) {
            if (!file_exists($invoicesPath)) {
                mkdir($invoicesPath);
            }

            if (!file_exists($invoicesPath . '/' . $datePieces[0])) {
                mkdir($invoicesPath . '/' . $datePieces[0]);
            }

            if (!file_exists($invoicesPath . '/' . $datePieces[0] . '/' . $datePieces[1])) {
                mkdir($invoicesPath . '/' . $datePieces[0] . '/' . $datePieces[1]);
            }
        }

        return $fullPath;
    }

    /**
     * @Route("/display-invoice-action-new-version", name="displayInvoiceNewVersion")
     */
    public function displayInvoiceNewVersionAction(Request $request, EntityManager $em)
    {
        $id = $request->query->get('id');
        /** @var Invoice $invoice */
        $invoice = $em->getRepository('GCRMCRMBundle:Invoice')->find($id);

        $invoiceDate = $invoice->getCreatedDate();
        $datePieces = explode('-', $invoiceDate->format('Y-m-d'));

        $number = $invoice->getNumber();
        $invoiceFilename = str_replace('/', '-', $number);

        $invoicesPath = $this->get('kernel')->getRootDir() . '/../' . self::ROOT_RELATIVE_INVOICES_NEW_VERSION_PATH;

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
     * @Route("/display-invoice-from-custom-list", name="displayInvoiceFromCustomList")
     */
    public function displayInvoiceFromCustomListAction(Request $request, EasyAdminModel $easyAdminModel, EntityManager $em, \Wecoders\InvoiceBundle\Service\InvoiceModel $invoiceModel)
    {
        $id = $request->query->get('id');
        $entity = $request->query->get('entity');

        $entityClass = $easyAdminModel->getEntityClassByEntityName($entity);
        $entityDirectory = $easyAdminModel->getEntityDirectoryByEntityName($entity);
        if (!$entityDirectory) {
            die('Brak ustawień "directory" w konfiguracji tabel. Skontaktuj się z administratorem.');
        }

        $object = $em->getRepository($entityClass)->find($id);
        $filename = $invoiceModel->generateFilenameFromNumber($object->getNumber());
        $dirDatePart = $invoiceModel->invoiceDirDataPiece($object);
        $fullDirectory = $entityDirectory . '/' . $dirDatePart;

        $invoiceModel->displayInvoice($fullDirectory, $filename);
    }

    /**
     * @Route("/display-invoice-proforma-action", name="displayInvoiceProforma")
     */
    public function displayInvoiceProformaAction(Request $request, EntityManager $em)
    {
        $id = $request->query->get('id');
        /** @var Invoice $invoice */
        $invoice = $em->getRepository('GCRMCRMBundle:InvoiceProforma')->find($id);

        $invoiceDate = $invoice->getCreatedDate();
        $datePieces = explode('-', $invoiceDate->format('Y-m-d'));

        $number = $invoice->getNumber();
        $invoiceFilename = str_replace('/', '-', $number);

        $invoicesPath = $this->get('kernel')->getRootDir() . '/../' . self::ROOT_RELATIVE_INVOICES_PROFORMA_PATH;

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
     * @Route("/display-correction-action-new-version", name="displayCorrectionNewVersion")
     */
    public function displayCorrectionNewVersionAction(Request $request, EntityManager $em)
    {
        $id = $request->query->get('id');
        /** @var Invoice $invoice */
        $invoice = $em->getRepository('GCRMCRMBundle:InvoiceCorrection')->find($id);

        $invoiceDate = $invoice->getCreatedDate();
        $datePieces = explode('-', $invoiceDate->format('Y-m-d'));

        $number = $invoice->getNumber();
        $invoiceFilename = str_replace('/', '-', $number);

        $invoicesPath = $this->get('kernel')->getRootDir() . '/../' . self::ROOT_RELATIVE_CORRECTIONS_NEW_VERSION_PATH;

        $fullInvoicePath = $invoicesPath . $datePieces[0] . '/' . $datePieces[1] . '/' . $invoiceFilename;
        $fullInvoicePathWithExtension = $fullInvoicePath . '.pdf';

        if (file_exists($fullInvoicePathWithExtension)) {
            $fullInvoicePath = $fullInvoicePathWithExtension;
        }

        header('Content-type: application/pdf');
        echo readfile($fullInvoicePath);
        die;
    }

    /**
     * @Route("/invoices-send-to-server", name="invoicesSendToServer")
     */
    public function invoicesSendToServerAction(Request $request)
    {
        $dataUploader = new DataUploader();

        if (!file_exists($this->getParameter('upload_invoices_temp_input_path'))) {
            mkdir($this->getParameter('upload_invoices_temp_input_path'));
        }

        $dataUploader->uploadAttachment(['pdf'], $this->getParameter('upload_invoices_temp_input_path'));
        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/invoices-clear-temp-input-dir", name="invoicesClearTempInputDir")
     */
    public function invoicesClearTempInputDirAction(Request $request, InvoiceModel $invoiceModel, FileActionsModel $fileActionsModel)
    {
        $directoryInput = $this->getParameter('upload_invoices_temp_input_path');
        if (!$directoryInput) {
            throw new Exception();
        }

        $tempInputInvoices = $invoiceModel->getInvoicesFromDirectory($directoryInput);

        $fileActionsModel->deleteFiles($directoryInput, $tempInputInvoices);

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/invoices-clear-temp-output-dir", name="invoicesClearTempOutputDir")
     */
    public function invoicesClearTempOutputDirAction(Request $request, InvoiceModel $invoiceModel, FileActionsModel $fileActionsModel)
    {
        $directoryOutput = $this->getParameter('upload_invoices_temp_output_path');
        if (!$directoryOutput) {
            throw new Exception();
        }

        $tempInputInvoices = $invoiceModel->getInvoicesFromDirectory($directoryOutput);

        $fileActionsModel->deleteFiles($directoryOutput, $tempInputInvoices);

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @Route("/importer-gas", name="importerGas")
     */
    public function importerGasAction(Request $request, EntityManager $em)
    {
        $form = $this->createForm(ImporterGasType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rows = $this->getDataRows($form->get('file')->getData(), 3, 'AU');

            $statusContractAuthorizationPositive= $em->getRepository('GCRMCRMBundle:StatusContractAuthorization')->findOneBy([
                'code' => 'positive'
            ]);
            if (!$statusContractAuthorizationPositive) {
                die('Pozytywny status autoryzacji nie został ustawiony. Skontaktuj się z administratorem.');
            }

            $verificationDepartment = $em->getRepository('GCRMCRMBundle:StatusDepartment')->findOneBy([
                'code' => 'verification'
            ]);
            if (!$verificationDepartment) {
                die('Status departamentu weryfikacji nie został ustawiony. Skontaktuj się z administratorem.');
            }

            foreach ($rows as $item) {
                // check if client already exists
                $client = $em->getRepository('GCRMCRMBundle:Client')->findOneBy(['pesel' => $item[6]]);

                if (!$client) {
                    $client = new Client();
                    $client->setName($item[4]);
                    $client->setSurname($item[5]);
                    $client->setPesel($item[6]);
                    ////////// dokument
                    $client->setIdNr($item[8]);
                    $client->setTelephoneNr($item[9]);
                    $client->setEmail($item[10]);
                    $client->setZipCode($item[11]);
                    $client->setPostOffice($item[12]);
                    $client->setCity($item[13]);
                    $client->setStreet($item[14]);
                    $client->setHouseNr($item[15]);
                    $client->setApartmentNr($item[16]);
                    $client->setCorrespondenceZipCode($item[17]);
                    $client->setPostOffice($item[18]);
                    $client->setCorrespondenceCity($item[19]);
                    $client->setCorrespondenceStreet($item[20]);
                    $client->setCorrespondenceHouseNr($item[21]);
                    $client->setCorrespondenceApartmentNr($item[22]);
                    $client->setIsMarkedToGenerateInvoice(false);
                    $client->setIsInvoiceGenerated(false);
                }

                $contract = new ContractGas();
                $contract->setIsOnPackageList(false);
                $contract->setIsReturned(false);
                $contract->setIsDownloaded(false);
                $contract->setAdvisorCode($item[0]);
                $contract->setCourierName($item[2]);
                $contract->setCourierSurname($item[3]);
                $contract->setAddressGasZipCode($item[23]);
                $contract->setAddressGasPostOffice($item[24]);
                $contract->setAddressGasCity($item[25]);
                $contract->setAddressGasStreet($item[26]);
                $contract->setAddressGasHouseNr($item[27]);
                $contract->setAddressGasApartmentNr($item[28]);
                $contract->setMeasuringSystemId($item[29]);
                $contract->setGasMeterFabricNr($item[30]);
                $contract->setContractualYearFrom($this->formatDate($item[31]));
                $contract->setContractualYearTo($this->formatDate($item[32]));
                $contract->setContractNumber($item[33]);
                $contract->setCurrentSeller($item[34]);
                $contract->setSignDate($this->formatDate($item[35]));
                $contract->setContractFromDate($this->formatDate($item[36]));
                $contract->setContractToDate($this->formatDate($item[37]));
                $contract->setPeriodOfNotice($item[38]);
                $contract->setPreviousSellerTariff($item[39]);
                $contract->setConsumption($item[40]);
                $contract->setOsdTariff($item[41]);
                $contract->setPepTariff($item[42]);
                $contract->setPsgBranch($item[43]);
                //////////////// status umowy
                $contract->setAgent($item[45]);
                /////////////// status weryfikacji

                $contract->setStatusDepartment($verificationDepartment);
                $contract->setStatusAuthorization($statusContractAuthorizationPositive);

                $clientAndContractGas = new ClientAndContractGas();
                $clientAndContractGas->setClient($client);
                $clientAndContractGas->setContract($contract);

                $em->persist($client);
                $em->persist($contract);
                $em->persist($clientAndContractGas);

                $em->flush();
            }

            $this->addFlash('success', 'Dane zostały prawidłowo zaimportowane.');
            return $this->redirectToRoute('importerGas');
        }


        return $this->render('@GCRMCRM/Default/importer-gas.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function formatDateFromString($dateString, $delimiter = '.')
    {
        if (!$dateString) {
            return null;
        }

        $pieces = explode($delimiter, $dateString);
        if (count($pieces) != 3) {
            $pieces = explode('-', $dateString);
            if (count($pieces) != 3) {
                return null;
            }
        }

        $date = new \DateTime();
        $date->setDate($pieces[2], $pieces[1], $pieces[0]);
        $date->setTime(0, 0, 0);

        return $date;
    }

    /**
     * @Route("/importer-energy", name="importerEnergy")
     */
    public function importerEnergyAction(Request $request, EntityManager $em)
    {
        $form = $this->createForm(ImporterEnergyType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rows = $this->getDataRows($form->get('file')->getData(), 2, 'AQ');

            $statusContractAuthorizationPositive= $em->getRepository('GCRMCRMBundle:StatusContractAuthorization')->findOneBy([
                'code' => 'positive'
            ]);
            if (!$statusContractAuthorizationPositive) {
                die('Pozytywny status autoryzacji nie został ustawiony. Skontaktuj się z administratorem.');
            }

            $verificationDepartment = $em->getRepository('GCRMCRMBundle:StatusDepartment')->findOneBy([
                'code' => 'verification'
            ]);
            if (!$verificationDepartment) {
                die('Status departamentu weryfikacji nie został ustawiony. Skontaktuj się z administratorem.');
            }

            foreach ($rows as $item) {
                // check if client already exists
                $client = $em->getRepository('GCRMCRMBundle:Client')->findOneBy(['pesel' => $item[5]]);

                if (!$client) {
                    $nameAndSurname = explode(' ', $item[2]);

                    $client = new Client();
                    $client->setName(isset($nameAndSurname[0]) && $nameAndSurname[0] ? $nameAndSurname[0] : null);
                    $client->setSurname(isset($nameAndSurname[1]) && $nameAndSurname[1] ? $nameAndSurname[1] : null);
                    $client->setNip($item[3]);
                    $client->setRegon($item[4]);
                    $client->setPesel($item[5]);
                    $client->setTelephoneNr($item[6]);
                    $client->setEmail($item[7]);
                    $client->setZipCode($item[8]);
                    $client->setPostOffice($item[9]);
                    $client->setCity($item[10]);
                    $client->setStreet($item[11]);
                    $client->setHouseNr($item[12]);
                    $client->setApartmentNr($item[13]);
                    $client->setCorrespondenceZipCode($item[14]);
                    $client->setPostOffice($item[15]);
                    $client->setCorrespondenceCity($item[16]);
                    $client->setCorrespondenceStreet($item[17]);
                    $client->setCorrespondenceHouseNr($item[18]);
                    $client->setCorrespondenceApartmentNr($item[19]);
                    $client->setIsMarkedToGenerateInvoice(false);
                    $client->setIsInvoiceGenerated(false);
                }

                $contract = new ContractEnergy();
                $contract->setAdvisorCode($item[0]);
                $contract->setChangeOfSeller($item[20]);
                $contract->setDistributor($item[21]);
                $contract->setDistributorBranch($item[22]);
                $contract->setCurrentSeller($item[23]);
                $contract->setSignDate($this->formatDate($item[24]));
                $contract->setContractFromDate($this->formatDate($item[25]));
                $contract->setContractToDate($this->formatDate($item[26]));
                $contract->setPeriodOfNotice($item[27]);
                $contract->setProduct($item[28]);
                $contract->setPpeName($item[29]);
                $contract->setAddressPpeZipCode($item[30]);
                $contract->setAddressPpePostOffice($item[31]);
                $contract->setAddressPpeCity($item[32]);
                $contract->setAddressPpeStreet($item[33]);
                $contract->setAddressPpeHouseNr($item[34]);
                $contract->setAddressPpeApartmentNr($item[35]);
                $contract->setPpeCode($item[36]);
                $contract->setPpeCounterNr($item[37]);
                $contract->setPpeRegistrationNr($item[38]);
                $contract->setTariffGroup($item[39]);
                $contract->setConsumptionEnergyAnnual($item[40]);
                $contract->setProxy($item[41]);
                $contract->setAgent($item[42]);
                //////////

                $contract->setStatusDepartment($verificationDepartment);
                $contract->setStatusAuthorization($statusContractAuthorizationPositive);

                $clientAndContractEnergy = new ClientAndContractEnergy();
                $clientAndContractEnergy->setClient($client);
                $clientAndContractEnergy->setContract($contract);

                $em->persist($client);
                $em->persist($contract);
                $em->persist($clientAndContractEnergy);

                $em->flush();
            }

            $this->addFlash('success', 'Dane zostały prawidłowo zaimportowane.');
            return $this->redirectToRoute('importerEnergy');
        }


        return $this->render('@GCRMCRM/Default/importer-energy.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/importer-energy-update-dates", name="importerEnergyUpdateDates")
     */
    public function importerEnergyUpdateDatesAction(Request $request, EntityManager $em)
    {
        $form = $this->createForm(ImporterEnergyType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rows = $this->getDataRows($form->get('file')->getData(), 2, 'AQ');

            foreach ($rows as $item) {
                $contracts = $this->getDoctrine()->getRepository('GCRMCRMBundle:ContractEnergy')->findBy([
                    'ppeCode' => $item[36],
                    'ppeCounterNr' => $item[37],
                    'ppeRegistrationNr' => $item[38],
                    'consumptionEnergyAnnual' => $item[40]
                ]);

                if (!$contracts) {
                    continue;
                }
                foreach ($contracts as $contract) {
                    $contract->setSignDate($this->formatDateFromString($item[24]));
                    $contract->setContractFromDate($this->formatDateFromString($item[25]));
                    $contract->setContractToDate($this->formatDateFromString($item[26]));

                    $em->persist($contract);
                    $em->flush();
                }
            }

            $this->addFlash('success', 'Dane zostały prawidłowo zaimportowane.');
            return $this->redirectToRoute('importerEnergyUpdateDates');
        }

        return $this->render('@GCRMCRM/Default/importer-energy-update-dates.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/invoices-download", name="invoicesDownload")
     */
    public function invoicesDownloadAction(Request $request, InvoiceModel $invoiceModel)
    {
        $slashPathModel = new SlashPathModel();
        $directoryOutput = $this->getParameter('upload_invoices_temp_output_path');
        $directoryOutput = $slashPathModel->addSlash($directoryOutput);

        $files = $invoiceModel->getInvoicesFromDirectory($directoryOutput);
        if (!count($files)) {
            return $this->redirect($request->headers->get('referer'));
        }

        $zip = new \ZipArchive();
        $zipName = 'Documents_'.time().".zip";
        $zip->open($directoryOutput . $zipName,  \ZipArchive::CREATE);
        foreach ($files as $f) {
            $zip->addFromString(basename($f),  file_get_contents($directoryOutput . $f));
        }
        $zip->close();

        $response = new Response(file_get_contents($directoryOutput . $zipName));
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $zipName . '"');
        $response->headers->set('Content-length', filesize($directoryOutput . $zipName));

        unlink($directoryOutput . $zipName);

        return $response;
    }

}
