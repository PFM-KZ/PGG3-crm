<?php

namespace Wecoders\EnergyBundle\Service\ListSearcher;

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

class PaymentRequest extends ListSearcher implements EntityListSearcherInterface
{
    private $entity = 'Wecoders\EnergyBundle\Entity\PaymentRequest';

    protected $exporterTableName = 'paymentRequest';

    private $twigTemplate = '@WecodersEnergyBundle/default/parts/listSearch/payment-request.html.twig';

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
        if ($request->query->get('lsCreatedDateFrom')) {
            $queryBuilder->setParameter('lsCreatedDateFrom', $request->query->get('lsCreatedDateFrom'));
        }

        if ($request->query->get('lsCreatedDateTo')) {
            $queryBuilder->setParameter('lsCreatedDateTo', $request->query->get('lsCreatedDateTo'));
        }

        if ($request->query->get('lsDateOfPaymentFrom')) {
            $queryBuilder->setParameter('lsDateOfPaymentFrom', $request->query->get('lsDateOfPaymentFrom'));
        }

        if ($request->query->get('lsDateOfPaymentTo')) {
            $queryBuilder->setParameter('lsDateOfPaymentTo', $request->query->get('lsDateOfPaymentTo'));
        }

        if ($request->query->get('lsIsPaid')) {
            $queryBuilder->setParameter('lsIsPaid', $request->query->get('lsIsPaid'));
        }

        if ($request->query->get('lsPesel')) {
            $queryBuilder->setParameter('lsPesel', $request->query->get('lsPesel'));
        }

        if ($request->query->get('lsContractNumber')) {
            $queryBuilder->setParameter('lsContractNumber', $request->query->get('lsContractNumber'));
        }

        if ($request->query->get('lsName')) {
            $queryBuilder->setParameter('lsName', $request->query->get('lsName'));
        }

        if ($request->query->get('lsSurname')) {
            $queryBuilder->setParameter('lsSurname', $request->query->get('lsSurname'));
        }

        if ($request->query->get('lsNip')) {
            $queryBuilder->setParameter('lsNip', $request->query->get('lsNip'));
        }

        if ($request->query->get('lsBadgeId')) {
            $queryBuilder->setParameter('lsBadgeId', $request->query->get('lsBadgeId'));
        }

        if ($request->query->get('lsTelephoneNr')) {
            $queryBuilder->setParameter('lsTelephoneNr', $request->query->get('lsTelephoneNr'));
        }
    }

    public function addFields(FormBuilder $builder, $options, EntityManager $em)
    {
        $lsCreatedDateFrom = isset($options['data']['lsCreatedDateFrom']) && $options['data']['lsCreatedDateFrom'] ? \DateTime::createFromFormat('Y-m-d', $options['data']['lsCreatedDateFrom']): null;
        $lsCreatedDateTo = isset($options['data']['lsCreatedDateTo']) && $options['data']['lsCreatedDateTo'] ? \DateTime::createFromFormat('Y-m-d', $options['data']['lsCreatedDateTo']): null;
        $lsDateOfPaymentFrom = isset($options['data']['lsDateOfPaymentFrom']) && $options['data']['lsDateOfPaymentFrom'] ? \DateTime::createFromFormat('Y-m-d', $options['data']['lsDateOfPaymentFrom']): null;
        $lsDateOfPaymentTo = isset($options['data']['lsDateOfPaymentTo']) && $options['data']['lsDateOfPaymentTo'] ? \DateTime::createFromFormat('Y-m-d', $options['data']['lsDateOfPaymentTo']): null;

        $builder
            ->add('lsPesel', TextType::class, [
                'label' => 'PESEL',
            ])
            ->add('lsContractNumber', TextType::class, [
                'label' => 'Numer umowy',
            ])
            ->add('lsTelephoneNr', TextType::class, [
                'label' => 'Numer telefonu',
            ])
            ->add('lsCreatedDateFrom', DateType::class, [
                'label' => 'Data utworzenia od',
                'widget' => 'single_text',
                'data' => $lsCreatedDateFrom,
                'attr' => [
                    'class' => 'datepicker'
                ]
            ])
            ->add('lsCreatedDateTo', DateType::class, [
                'label' => 'do',
                'widget' => 'single_text',
                'data' => $lsCreatedDateTo,
                'attr' => [
                    'class' => 'datepicker'
                ]
            ])
            ->add('lsDateOfPaymentFrom', DateType::class, [
                'label' => 'Termin płatności od',
                'widget' => 'single_text',
                'data' => $lsDateOfPaymentFrom,
                'attr' => [
                    'class' => 'datepicker'
                ]
            ])
            ->add('lsDateOfPaymentTo', DateType::class, [
                'label' => 'do',
                'widget' => 'single_text',
                'data' => $lsDateOfPaymentTo,
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
            ])
            ->add('lsNip', TextType::class, [
                'label' => 'NIP',
            ])
            ->add('lsBadgeId', TextType::class, [
                'label' => 'Nr rach.',
            ])
        ;
    }

    public function addQuery(Request $request, &$dqlAnd = [], &$dqlOr = [], $statusDepartments)
    {
        $lsCreatedDateFrom = $request->query->get('lsCreatedDateFrom');
        $lsCreatedDateTo = $request->query->get('lsCreatedDateTo');
        $lsDateOfPaymentFrom = $request->query->get('lsDateOfPaymentFrom');
        $lsDateOfPaymentTo = $request->query->get('lsDateOfPaymentTo');

        if ($request->query->get('lsPesel')) {
            $dqlAnd[] = ' entity.clientPesel = :lsPesel';
        }
        if ($request->query->get('lsContractNumber')) {
            $dqlAnd[] = ' entity.contractNumber = :lsContractNumber';
        }
        if ($request->query->get('lsName')) {
            $dqlAnd[] = ' entity.clientName = :lsName';
        }
        if ($request->query->get('lsSurname')) {
            $dqlAnd[] = ' entity.clientSurname = :lsSurname';
        }
        if ($request->query->get('lsNip')) {
            $dqlAnd[] = ' entity.clientNip = :lsNip';
        }
        if ($request->query->get('lsBadgeId')) {
            $dqlAnd[] = ' entity.badgeId = :lsBadgeId';
        }
        if ($request->query->get('lsTelephoneNr')) {
            $dqlAnd[] = ' jclient.telephoneNr = :lsTelephoneNr';
        }


        if ($lsCreatedDateFrom) {
            $dqlAnd[] = ' entity.createdDate >= :lsCreatedDateFrom';
        }
        if ($lsCreatedDateTo) {
            $dqlAnd[] = ' entity.createdDate <= :lsCreatedDateTo';
        }

        if ($lsDateOfPaymentFrom) {
            $dqlAnd[] = ' entity.dateOfPayment >= :lsDateOfPaymentFrom';
        }
        if ($lsDateOfPaymentTo) {
            $dqlAnd[] = ' entity.dateOfPayment <= :lsDateOfPaymentTo';
        }
    }
}