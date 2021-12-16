<?php

namespace GCRM\CRMBundle\Service\ListSearcher;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use GCRM\CRMBundle\Entity\InvoiceInterface;
use GCRM\CRMBundle\Service\ListDataExporterInterface;
use GCRM\CRMBundle\Service\ListFilesDownloaderInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\Request;
use GCRM\CRMBundle\Service\ListSearcher\ListSearcher;
use GCRM\CRMBundle\Service\ListSearcher\EntityListSearcherInterface;

class Payment extends ListSearcher implements EntityListSearcherInterface
{
    private $entity = 'GCRM\CRMBundle\Entity\Payment';

    private $twigTemplate = '@GCRMCRMBundle/Default/parts/listSearch/payment.html.twig';

    protected $joinTables = [
//        [
//            'entity' => 'GCRM\CRMBundle\Entity\Client',
//            'as' => 'jclient',
//            'condition' => 'entity.badgeId = jclient.badgeId',
//        ]
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
        if ($request->query->get('lsCreatedAtFrom')) {
            $queryBuilder->setParameter('lsCreatedAtFrom', $request->query->get('lsCreatedAtFrom'));
        }
        if ($request->query->get('lsCreatedAtTo')) {
            $queryBuilder->setParameter('lsCreatedAtTo', $request->query->get('lsCreatedAtTo'));
        }
        if ($request->query->get('lsDateFrom')) {
            $queryBuilder->setParameter('lsDateFrom', $request->query->get('lsDateFrom'));
        }
        if ($request->query->get('lsDateTo')) {
            $queryBuilder->setParameter('lsDateTo', $request->query->get('lsDateTo'));
        }
        if ($request->query->get('lsValueFrom')) {
            $queryBuilder->setParameter('lsValueFrom', $request->query->get('lsValueFrom'));
        }
        if ($request->query->get('lsValueTo')) {
            $queryBuilder->setParameter('lsValueTo', $request->query->get('lsValueTo'));
        }
//        if ($request->query->get('lsPesel')) {
//            $queryBuilder->setParameter('lsPesel', '%' . $request->query->get('lsPesel') . '%');
//        }
//        if ($request->query->get('lsTelephoneNr')) {
//            $queryBuilder->setParameter('lsTelephoneNr', '%' . $request->query->get('lsTelephoneNr') . '%');
//        }
//        if ($request->query->get('lsName')) {
//            $queryBuilder->setParameter('lsName', '%' . $request->query->get('lsName') . '%');
//        }
//        if ($request->query->get('lsSurname')) {
//            $queryBuilder->setParameter('lsSurname', '%' . $request->query->get('lsSurname') . '%');
//        }
//        if ($request->query->get('lsNip')) {
//            $queryBuilder->setParameter('lsNip', '%' . $request->query->get('lsNip') . '%');
//        }
        if ($request->query->get('lsBadgeId')) {
            $queryBuilder->setParameter('lsBadgeId', '%' . $request->query->get('lsBadgeId') . '%');
        }
        if ($request->query->get('lsSenderAccountNumber')) {
            $queryBuilder->setParameter('lsSenderAccountNumber', '%' . $request->query->get('lsSenderAccountNumber') . '%' );
        }
    }

    public function addFields(FormBuilder $builder, $options, EntityManager $em)
    {
        $lsCreatedAtFrom = isset($options['data']['lsCreatedAtFrom']) && $options['data']['lsCreatedAtFrom'] ? \DateTime::createFromFormat('Y-m-d', $options['data']['lsCreatedAtFrom']): null;
        $lsCreatedAtTo = isset($options['data']['lsCreatedAtTo']) && $options['data']['lsCreatedAtTo'] ? \DateTime::createFromFormat('Y-m-d', $options['data']['lsCreatedAtTo']): null;

        $lsDateFrom = isset($options['data']['lsDateFrom']) && $options['data']['lsDateFrom'] ? \DateTime::createFromFormat('Y-m-d', $options['data']['lsDateFrom']): null;
        $lsDateTo = isset($options['data']['lsDateTo']) && $options['data']['lsDateTo'] ? \DateTime::createFromFormat('Y-m-d', $options['data']['lsDateTo']): null;

        $builder
            ->add('lsCreatedAtFrom', DateType::class, [
                'label' => 'Data utworzenia od',
                'data' => $lsCreatedAtFrom,
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'datepicker'
                ]
            ])
            ->add('lsCreatedAtTo', DateType::class, [
                'label' => 'Data utworzenia do',
                'data' => $lsCreatedAtTo,
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'datepicker'
                ]
            ])
            ->add('lsDateFrom', DateType::class, [
                'label' => 'Data płatności od',
                'data' => $lsDateFrom,
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'datepicker'
                ]
            ])
            ->add('lsDateTo', DateType::class, [
                'label' => 'Data płatności do',
                'data' => $lsDateTo,
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'datepicker'
                ]
            ])
            ->add('lsValueFrom', TextType::class, [
                'label' => 'Kwota od',
            ])
            ->add('lsValueTo', TextType::class, [
                'label' => 'Kwota do',
            ])
//            ->add('lsPesel', TextType::class, [
//                'label' => 'PESEL',
//            ])
//            ->add('lsTelephoneNr', TextType::class, [
//                'label' => 'Numer telefonu',
//            ])
//            ->add('lsName', TextType::class, [
//                'label' => 'Imię',
//            ])
//            ->add('lsSurname', TextType::class, [
//                'label' => 'Nazwisko',
//            ])
//            ->add('lsNip', TextType::class, [
//                'label' => 'NIP',
//            ])
            ->add('lsBadgeId', TextType::class, [
                'label' => 'Nr rach.',
            ])
            ->add('lsSenderAccountNumber', TextType::class, [
                'label' => 'Numer rachunku nadawcy',
            ])
        ;
    }

    public function addQuery(Request $request, &$dqlAnd = [], &$dqlOr = [], $statusDepartments)
    {
        if ($request->query->get('lsCreatedAtFrom')) {
            $dqlAnd[] = ' entity.createdAt >= :lsCreatedAtFrom';
        }
        if ($request->query->get('lsCreatedAtTo')) {
            $dqlAnd[] = ' entity.createdAt <= :lsCreatedAtTo';
        }
        if ($request->query->get('lsDateFrom')) {
            $dqlAnd[] = ' entity.date >= :lsDateFrom';
        }
        if ($request->query->get('lsDateTo')) {
            $dqlAnd[] = ' entity.date <= :lsDateTo';
        }
        if ($request->query->get('lsValueFrom')) {
            $dqlAnd[] = ' entity.value >= :lsValueFrom';
        }
        if ($request->query->get('lsValueTo')) {
            $dqlAnd[] = ' entity.value <= :lsValueTo';
        }
//        if ($request->query->get('lsPesel')) {
//            $dqlAnd[] = ' jclient.pesel LIKE :lsPesel';
//        }
//        if ($request->query->get('lsTelephoneNr')) {
//            $dqlAnd[] = ' jclient.telephoneNr LIKE :lsTelephoneNr';
//        }
//        if ($request->query->get('lsName')) {
//            $dqlAnd[] = ' jclient.name LIKE :lsName';
//        }
//        if ($request->query->get('lsSurname')) {
//            $dqlAnd[] = ' jclient.surname LIKE :lsSurname';
//        }
//        if ($request->query->get('lsNip')) {
//            $dqlAnd[] = ' jclient.nip LIKE :lsNip';
//        }
        if ($request->query->get('lsBadgeId')) {
            $dqlAnd[] = ' entity.badgeId LIKE :lsBadgeId';
        }
        if ($request->query->get('lsSenderAccountNumber')) {
            $dqlAnd[] = ' entity.senderAccountNumber LIKE :lsSenderAccountNumber';
        }
    }
}