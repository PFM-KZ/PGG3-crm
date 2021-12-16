<?php

namespace Wecoders\EnergyBundle\Service\ListSearcher;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use GCRM\CRMBundle\Entity\ContractType;
use GCRM\CRMBundle\Entity\InvoiceInterface;
use GCRM\CRMBundle\Service\ListDataExporterInterface;
use GCRM\CRMBundle\Service\ListFilesDownloaderInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\Request;
use GCRM\CRMBundle\Service\ListSearcher\ListSearcher;
use GCRM\CRMBundle\Service\ListSearcher\EntityListSearcherInterface;

class InvoiceProformaEnergy extends ListSearcher implements EntityListSearcherInterface, ListFilesDownloaderInterface
{
    const ROOT_RELATIVE_INVOICES_NEW_VERSION_PATH = 'var/data/uploads/invoices-proforma-energy/';

    private $entity = 'Wecoders\EnergyBundle\Entity\InvoiceProforma';

    protected $exporterTableName = 'invoiceProformaEnergy';

    private $twigTemplate = '@GCRMCRMBundle/Default/parts/listSearch/invoice.html.twig';

    protected $joinTables = [
        [
            'entity' => 'GCRM\CRMBundle\Entity\Client',
            'as' => 'jclient',
            'condition' => 'entity.client = jclient',
        ]
    ];

    public function __construct(Request $request, ContainerInterface $container)
    {
        $this->request = $request;
        $this->container = $container;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return string
     */
    public function getTwigTemplate()
    {
        return $this->twigTemplate;
    }

    /**
     * @param string $parameters
     */
    public function addParameters(QueryBuilder $queryBuilder, Request $request)
    {
        if ($request->query->get('lsInvoiceCreatedDateFrom')) {
            $queryBuilder->setParameter('lsInvoiceCreatedDateFrom', $request->query->get('lsInvoiceCreatedDateFrom'));
        }

        if ($request->query->get('lsInvoiceCreatedDateTo')) {
            $queryBuilder->setParameter('lsInvoiceCreatedDateTo', $request->query->get('lsInvoiceCreatedDateTo'));
        }

        if ($request->query->get('lsInvoiceNumber')) {
            $queryBuilder->setParameter('lsInvoiceNumber', '%' . $request->query->get('lsInvoiceNumber') . '%');
        }

        if ($request->query->get('lsPesel')) {
            $queryBuilder->setParameter('lsPesel', '%' . $request->query->get('lsPesel') . '%');
        }

        if ($request->query->get('lsName')) {
            $queryBuilder->setParameter('lsName', '%' . $request->query->get('lsName') . '%');
        }

        if ($request->query->get('lsSurname')) {
            $queryBuilder->setParameter('lsSurname', '%' . $request->query->get('lsSurname') . '%');
        }

        if ($request->query->get('lsNip')) {
            $queryBuilder->setParameter('lsNip', '%' . $request->query->get('lsNip') . '%');
        }

        if ($request->query->get('lsBadgeId')) {
            $queryBuilder->setParameter('lsBadgeId', '%' . $request->query->get('lsBadgeId') . '%');
        }

        if ($request->query->get('lsTelephoneNr')) {
            $queryBuilder->setParameter('lsTelephoneNr', '%' . $request->query->get('lsTelephoneNr') . '%');
        }

        if ($request->query->get('lsContractType')) {
            $queryBuilder->setParameter('lsContractType', $request->query->get('lsContractType'));
        }
    }

    public function addFields(FormBuilder $builder, $options, EntityManager $em)
    {
        $lsInvoiceCreatedDateFrom = isset($options['data']['lsInvoiceCreatedDateFrom']) && $options['data']['lsInvoiceCreatedDateFrom'] ? \DateTime::createFromFormat('Y-m-d', $options['data']['lsInvoiceCreatedDateFrom']): null;
        $lsInvoiceCreatedDateTo = isset($options['data']['lsInvoiceCreatedDateTo']) && $options['data']['lsInvoiceCreatedDateTo'] ? \DateTime::createFromFormat('Y-m-d', $options['data']['lsInvoiceCreatedDateTo']): null;

        $contractTypes = $em->getRepository('GCRMCRMBundle:ContractType')->findAll();
        /** @var ContractType $contractType */
        foreach ($contractTypes as $contractType) {
            $contractType->setTitle(mb_strtoupper($contractType->getTitle()));
            $contractType->setCode(mb_strtoupper($contractType->getCode()));
        }

        $lsContractType = null;
        if (isset($options['data']['lsContractType'])) {
            foreach ($contractTypes as $contractType) {
                if ($contractType->getCode() == $options['data']['lsContractType']) {
                    $lsContractType = $contractType;
                    break;
                }
            }
        }

        $builder
            ->add('lsSalesRepresentative', ChoiceType::class, [
                'label' => 'Przedstawiciel handlowy',
                'disabled' => true,
            ])
            ->add('lsStatusDepartment', ChoiceType::class, [
                'label' => 'Aktualny departament',
                'choices' => null,
                'placeholder' => 'Wszystkie',
                'disabled' => true,
            ])
            ->add('lsStatusDepartmentStatus', ChoiceType::class, [
                'label' => 'Departament statusu',
                'choices' => null,
                'placeholder' => 'Wszystkie',
                'disabled' => true,
            ])
            ->add('lsActualStatus', ChoiceType::class, [
                'label' => 'Aktualny status',
                'choices' => null,
                'placeholder' => 'Wszystkie',
                'disabled' => true,
            ])
            ->add('lsStatusContract', ChoiceType::class, [
                'label' => 'Status departamentu',
                'choices' => null,
                'placeholder' => 'Wszystkie',
                'disabled' => true,
            ])
            ->add('lsBranch', ChoiceType::class, [
                'label' => 'Biuro',
                'choices' => null,
                'placeholder' => 'Wybierz...',
                'disabled' => true,
            ])
            ->add('lsPesel', TextType::class, [
                'label' => 'PESEL',
            ])
            ->add('lsTelephoneNr', TextType::class, [
                'label' => 'Numer telefonu',
            ])
            ->add('lsSignDateFrom', DateType::class, [
                'label' => 'Data podpisania od',
                'disabled' => true,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'datepicker'
                ]
            ])
            ->add('lsSignDateTo', DateType::class, [
                'label' => 'do',
                'disabled' => true,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'datepicker'
                ]
            ])
            ->add('lsCreatedDateFrom', DateType::class, [
                'label' => 'Data utworzenia od',
                'disabled' => true,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'datepicker'
                ]
            ])
            ->add('lsCreatedDateTo', DateType::class, [
                'label' => 'do',
                'disabled' => true,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'datepicker'
                ]
            ])
            ->add('lsName', TextType::class, [
                'label' => 'Imię',
            ])
            ->add('lsSurname', TextType::class, [
                'label' => 'Nazwisko',
            ])
            ->add('lsContractNumber', TextType::class, [
                'label' => 'Numer umowy',
                'disabled' => true,
            ])
            ->add('lsContractType', ChoiceType::class, [
                'label' => 'Typ umowy',
                'choices' => $contractTypes,
                'choice_value' => function ($entity = null) {
                    return is_object($entity) ? $entity->getCode() : '';
                },
                'choice_label' => function ($entity = null) {
                    return $entity ?: '';
                },
                'placeholder' => 'Wszystkie',
                'data' => $lsContractType,
            ])
            ->add('lsNip', TextType::class, [
                'label' => 'NIP',
            ])
            ->add('lsBadgeId', TextType::class, [
                'label' => 'Nr rach.',
            ])


            ->add('lsInvoiceCreatedDateFrom', DateType::class, [
                'label' => 'Data wystawienia dokumentu od',
                'data' => $lsInvoiceCreatedDateFrom,
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'datepicker'
                ]
            ])
            ->add('lsInvoiceCreatedDateTo', DateType::class, [
                'label' => 'do',
                'data' => $lsInvoiceCreatedDateTo,
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'datepicker'
                ]
            ])
            ->add('lsInvoiceNumber', TextType::class, [
                'label' => 'Numer dokumentu',
            ])
            ->add('lsBrand', ChoiceType::class, [
                'label' => 'Marka',
                'choices' => null,
                'placeholder' => 'Wybierz...',
                'disabled' => true,
            ])
            ->add('ppCode', TextType::class, [
                'label' => 'Kod PP',
                'disabled' => true,
            ])
        ;
    }

    public function addQuery(Request $request, &$dqlAnd = [], &$dqlOr = [], $statusDepartments)
    {
        $lsInvoiceCreatedDateFrom = $request->query->get('lsInvoiceCreatedDateFrom');
        $lsInvoiceCreatedDateTo = $request->query->get('lsInvoiceCreatedDateTo');

        if ($request->query->get('lsPesel')) {
            $dqlAnd[] = ' jclient.pesel LIKE :lsPesel';
        }
        if ($request->query->get('lsName')) {
            $dqlAnd[] = ' jclient.name LIKE :lsName';
        }
        if ($request->query->get('lsSurname')) {
            $dqlAnd[] = ' jclient.surname LIKE :lsSurname';
        }
        if ($request->query->get('lsNip')) {
            $dqlAnd[] = ' jclient.nip LIKE :lsNip';
        }
        if ($request->query->get('lsBadgeId')) {
            $dqlAnd[] = ' jclient.badgeId LIKE :lsBadgeId';
        }
        if ($request->query->get('lsTelephoneNr')) {
            $dqlAnd[] = ' jclient.telephoneNr LIKE :lsTelephoneNr';
        }


        if ($lsInvoiceCreatedDateFrom) {
            $dqlAnd[] = ' entity.createdDate >= :lsInvoiceCreatedDateFrom';
        }

        if ($lsInvoiceCreatedDateTo) {
            $dqlAnd[] = ' entity.createdDate <= :lsInvoiceCreatedDateTo';
        }

        if ($request->query->get('lsInvoiceNumber')) {
            $dqlAnd[] = ' entity.number LIKE :lsInvoiceNumber';
        }

        if ($request->query->get('lsContractType')) {
            $dqlAnd[] = ' entity.type LIKE :lsContractType';
        }
    }

    public function getRelativeRootPathToDirectory($entityClass = null)
    {
        return self::ROOT_RELATIVE_INVOICES_NEW_VERSION_PATH;
    }

    public function getFilesToDownload($kernelRootDir, $records, $entityClass = null)
    {
        $rootRelativePathToFiles = $this->getRelativeRootPathToDirectory($entityClass);

        $result = [];

        foreach ($records as $item) {
            $result[] = $this->getAbsolutePathFromRecordToDownload($kernelRootDir, $item, $rootRelativePathToFiles);
        }

        return $result;
    }

    private function getAbsolutePathFromRecordToDownload($kernelRootDir, $item, $relativePath)
    {
        /** @var InvoiceInterface $item */
        $invoiceDate = $item->getCreatedDate();
        $datePieces = explode('-', $invoiceDate->format('Y-m-d'));

        $number = $item->getNumber();
        $invoiceFilename = str_replace('/', '-', $number);

        $invoicesPath = $kernelRootDir . '/../' . $relativePath;

        $fullInvoicePath = $invoicesPath . $datePieces[0] . '/' . $datePieces[1] . '/' . $invoiceFilename;
        $fullInvoicePathWithExtension = $fullInvoicePath . '.pdf';

        if (file_exists($fullInvoicePathWithExtension)) {
            $fullInvoicePath = $fullInvoicePathWithExtension;
        }

        return $fullInvoicePath;
    }
}