<?php

namespace Wecoders\EnergyBundle\Service\ListSearcher;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use GCRM\CRMBundle\Entity\InvoiceInterface;
use GCRM\CRMBundle\Entity\User;
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

class ClientEnquiry extends ListSearcher implements EntityListSearcherInterface, ListFilesDownloaderInterface
{
    const ROOT_RELATIVE_CLIENT_ENQUIRY_PATH = 'var/data/uploads/client-enquiry/';

    private $entity = 'GCRM\CRMBundle\Entity\ClientEnquiry';

    protected $exporterTableName = 'client-enquiry';

    private $twigTemplate = '@GCRMCRMBundle/Default/parts/listSearch/client-enquiry.html.twig';

    protected $joinTables = [
        [
            'entity' => 'GCRM\CRMBundle\Entity\User',
            'as' => 'sruser',
            'condition' => 'entity.user = sruser.id',
        ],
        [
            'entity' => 'GCRM\CRMBundle\Entity\Seller',
            'as' => 'seseller',
            'condition' => 'entity.currentSellerObject = seseller.id',
        ],
        [
            'entity' => 'GCRM\CRMBundle\Entity\Distributor',
            'as' => 'didistributor',
            'condition' => 'entity.distributorObject = didistributor.id',
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
        if ($request->query->get('lsUser')) {
            $queryBuilder->setParameter('lsUser', $request->query->get('lsUser'));
        }
        if ($request->query->get('lsDateFrom')) {
            $queryBuilder->setParameter('lsDateFrom', $request->query->get('lsDateFrom'));
        }
        if ($request->query->get('lsDateTo')) {
            $queryBuilder->setParameter('lsDateTo', $request->query->get('lsDateTo'));
        }
        if ($request->query->get('lsName')) {
            $queryBuilder->setParameter('lsName', $request->query->get('lsName'));
        }
        if ($request->query->get('lsSurname')) {
            $queryBuilder->setParameter('lsSurname', $request->query->get('lsSurname'));
        }
        if ($request->query->get('lsPesel')) {
            $queryBuilder->setParameter('lsPesel', $request->query->get('lsPesel'));
        }
        if ($request->query->get('lsTelephoneNr')) {
            $queryBuilder->setParameter('lsTelephoneNr', $request->query->get('lsTelephoneNr'));
        }
    }

    public function addFields(FormBuilder $builder, $options, EntityManager $em)
    {
        $lsDateFrom = isset($options['data']['lsDateFrom']) && $options['data']['lsDateFrom'] ? \DateTime::createFromFormat('Y-m-d', $options['data']['lsDateFrom']): null;
        $lsDateTo = isset($options['data']['lsDateTo']) && $options['data']['lsDateTo'] ? \DateTime::createFromFormat('Y-m-d', $options['data']['lsDateTo']): null;

        $lsUserChoices = $em->getRepository(User::class)->findBy([
            'isSalesRepresentative' => true,
        ]);

        $selectedUser = isset($options['data']['lsUser']) ? $em->getRepository(User::class)->find($options['data']['lsUser']) : null;

        $builder
            ->add('lsDateFrom', DateType::class, [
                'label' => 'Data ankiety od',
                'data' => $lsDateFrom,
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'datepicker'
                ]
            ])
            ->add('lsDateTo', DateType::class, [
                'label' => 'do',
                'data' => $lsDateTo,
                'required' => false,
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
            ->add('lsPesel', TextType::class, [
                'label' => 'PESEL',
            ])
            ->add('lsUser', ChoiceType::class, [
                'label' => 'Handlowiec',
                'placeholder' => 'Wszyscy',
                'choices' => $lsUserChoices,
                'choice_value' => function ($entity = null) {
                    return is_object($entity) ? $entity->getId() : '';
                },
                'choice_label' => function ($entity = null) {
                    return $entity ? $entity->getName() . ' ' . $entity->getSurname() : '';
                },
                'data' => $selectedUser,

            ])
            ->add('lsTelephoneNr', TextType::class, [
                'label' => 'Telefon',
            ])
        ;
    }

    public function addQuery(Request $request, &$dqlAnd = [], &$dqlOr = [], $statusDepartments)
    {
        $lsEnquiryDateFrom = $request->query->get('lsDateFrom');
        $lsEnquiryDateTo = $request->query->get('lsDateTo');

        if ($request->query->get('lsUser')) {
            $dqlAnd[] = ' sruser.id = :lsUser';
        }
        if ($lsEnquiryDateFrom) {
            $dqlAnd[] = ' entity.createdAt >= :lsDateFrom';
        }
        if ($lsEnquiryDateTo) {
            $dqlAnd[] = ' entity.createdAt <= :lsDateTo';
        }
        if ($request->query->get('lsName')) {
            $dqlAnd[] = ' entity.name = :lsName';
        }
        if ($request->query->get('lsSurname')) {
            $dqlAnd[] = ' entity.surname = :lsSurname';
        }
        if ($request->query->get('lsPesel')) {
            $dqlAnd[] = ' entity.pesel = :lsPesel';
        }
        if ($request->query->get('lsTelephoneNr')) {
            $dqlAnd[] = ' entity.telephoneNr LIKE :lsTelephoneNr';
        }
    }

    public function getRelativeRootPathToDirectory($entityClass = null)
    {
        return self::ROOT_RELATIVE_CLIENT_ENQUIRY_PATH;
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