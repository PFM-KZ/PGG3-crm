<?php

namespace GCRM\CRMBundle\Service\ListSearcher;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\QueryBuilder;
use GCRM\CRMBundle\Entity\ContractInterface;
use GCRM\CRMBundle\Entity\ContractType;
use GCRM\CRMBundle\Entity\StatusContract;
use GCRM\CRMBundle\Entity\StatusContractAdministration;
use GCRM\CRMBundle\Entity\StatusDepartment;
use GCRM\CRMBundle\Entity\User;
use GCRM\CRMBundle\Service\ListDataExporterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\Request;
use Wecoders\EnergyBundle\Entity\Brand;

class ChangeStatusLog extends ListSearcher implements EntityListSearcherInterface
{
    private $entity = 'GCRM\CRMBundle\Entity\ChangeStatusLog';

    protected $exporterTableName = 'changeStatusLog';

    private $twigTemplate = '@GCRMCRMBundle/Default/parts/listSearch/change-status-log.html.twig';

    protected $joinTables = [];

    public function __construct(Request $request, ContainerInterface $container)
    {
        $this->request = $request;
        $this->container = $container;
        $this->user = $container->get('security.token_storage')->getToken()->getUser();
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

    public function addParameters(QueryBuilder $queryBuilder, Request $request)
    {
        if ($request->query->get('lsStatusDepartment')) {
            $queryBuilder->setParameter('lsStatusDepartment', $request->query->get('lsStatusDepartment'));
        }
        if ($request->query->get('lsCreatedDateFrom')) {
            $queryBuilder->setParameter('lsCreatedDateFrom', $request->query->get('lsCreatedDateFrom'));
        }
        if ($request->query->get('lsCreatedDateTo')) {
            $queryBuilder->setParameter('lsCreatedDateTo', $request->query->get('lsCreatedDateTo'));
        }
        if ($request->query->get('lsContractNumber')) {
            $queryBuilder->setParameter('lsContractNumber', $request->query->get('lsContractNumber'));
        }
        if ($request->query->get('lsStatusContractFrom')) {
            $queryBuilder->setParameter('lsStatusContractFrom', $request->query->get('lsStatusContractFrom'));
        }
        if ($request->query->get('lsStatusContractTo')) {
            $queryBuilder->setParameter('lsStatusContractTo', $request->query->get('lsStatusContractTo'));
        }
    }

    public function addFields(FormBuilder $builder, $options, EntityManager $em)
    {
        $lsCreatedDateFrom = isset($options['data']['lsCreatedDateFrom']) && $options['data']['lsCreatedDateFrom'] ? \DateTime::createFromFormat('Y-m-d', $options['data']['lsCreatedDateFrom']): null;
        $lsCreatedDateTo = isset($options['data']['lsCreatedDateTo']) && $options['data']['lsCreatedDateTo'] ? \DateTime::createFromFormat('Y-m-d', $options['data']['lsCreatedDateTo']): null;

        $statusDepartments = $em->getRepository('GCRMCRMBundle:StatusDepartment')->findAll();
        /** @var StatusDepartment $statusDepartment */
        $selectedStatusDepartment = isset($options['data']['lsStatusDepartment']) && $options['data']['lsStatusDepartment'] ? $options['data']['lsStatusDepartment'] : null;
        if ($selectedStatusDepartment) {
            foreach ($statusDepartments as $statusDepartment) {
                if ($statusDepartment->getId() == $selectedStatusDepartment) {
                    $selectedStatusDepartment = $statusDepartment;
                    break;
                }
            }
        }

        $statusContracts = $em->getRepository('GCRMCRMBundle:StatusContract')->findAll();
        $lsStatusContractFrom = null;
        if (isset($options['data']['lsStatusContractFrom'])) {
            /** @var StatusContract $statusContract */
            foreach ($statusContracts as $statusContract) {
                if ($statusContract->getId() == $options['data']['lsStatusContractFrom']) {
                    $lsStatusContractFrom = $statusContract;
                    break;
                }
            }
        }
        $lsStatusContractTo = null;
        if (isset($options['data']['lsStatusContractTo'])) {
            /** @var StatusContract $statusContract */
            foreach ($statusContracts as $statusContract) {
                if ($statusContract->getId() == $options['data']['lsStatusContractTo']) {
                    $lsStatusContractTo = $statusContract;
                    break;
                }
            }
        }

        $builder
            ->add('lsStatusDepartment', ChoiceType::class, [
                'label' => 'Departament',
                'choices' => $statusDepartments,
                'choice_value' => function ($entity = null) {
                    return is_object($entity) ? $entity->getId() : '';
                },
                'choice_label' => function ($entity = null) {
                    return $entity ?: '';
                },
                'placeholder' => 'Wszystkie',
                'data' => $selectedStatusDepartment,
            ])
            ->add('lsStatusContractFrom', ChoiceType::class, [
                'label' => 'Status początkowy',
                'choices' => $statusContracts,
                'choice_value' => function ($entity = null) {
                    return is_object($entity) ? $entity->getId() : '';
                },
                'choice_label' => function ($entity = null) {
                    return $entity ?: '';
                },
                'placeholder' => 'Wszystkie',
                'data' => $lsStatusContractFrom,
            ])
            ->add('lsStatusContractTo', ChoiceType::class, [
                'label' => 'Status końcowy',
                'choices' => $statusContracts,
                'choice_value' => function ($entity = null) {
                    return is_object($entity) ? $entity->getId() : '';
                },
                'choice_label' => function ($entity = null) {
                    return $entity ?: '';
                },
                'placeholder' => 'Wszystkie',
                'data' => $lsStatusContractTo,
            ])
            ->add('lsCreatedDateFrom', DateType::class, [
                'label' => 'Data utworzenia od',
                'data' => $lsCreatedDateFrom,
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'datepicker'
                ]
            ])
            ->add('lsCreatedDateTo', DateType::class, [
                'label' => 'do',
                'data' => $lsCreatedDateTo,
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'datepicker'
                ]
            ])
            ->add('lsContractNumber', TextType::class, [
                'label' => 'Numer umowy',
            ])
        ;
    }

    public function addQuery(Request $request, &$dqlAnd = [], &$dqlOr = [], $statusDepartments)
    {
        $lsCreatedDateFrom = $request->query->get('lsCreatedDateFrom');
        $lsCreatedDateTo = $request->query->get('lsCreatedDateTo');

        if ($lsCreatedDateFrom) {
            $dqlAnd[] = ' entity.createdAt >= :lsCreatedDateFrom';
        }

        if ($lsCreatedDateTo) {
            $dqlAnd[] = ' entity.createdAt <= :lsCreatedDateTo';
        }

        if ($request->query->get('lsContractNumber')) {
            $dqlAnd[] = 'entity.contractNumber = :lsContractNumber';
        }

        if ($request->query->get('lsStatusDepartment')) {
            $dqlAnd[] = 'entity.department = :lsStatusDepartment';
        }

        if ($request->query->get('lsStatusContractFrom')) {
            $dqlAnd[] = 'entity.fromStatus = :lsStatusContractFrom';
        }

        if ($request->query->get('lsStatusContractTo')) {
            $dqlAnd[] = 'entity.toStatus = :lsStatusContractTo';
        }
    }
}