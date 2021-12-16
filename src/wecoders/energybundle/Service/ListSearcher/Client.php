<?php

namespace Wecoders\EnergyBundle\Service\ListSearcher;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use GCRM\CRMBundle\Entity\ContractInterface;
use GCRM\CRMBundle\Entity\ContractType;
use GCRM\CRMBundle\Entity\StatusContract;
use GCRM\CRMBundle\Entity\StatusDepartment;
use GCRM\CRMBundle\Service\ListDataExporterInterface;
use GCRM\CRMBundle\Service\ListSearcher\ListSearcher;
use GCRM\CRMBundle\Service\ListSearcher\EntityListSearcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\Request;
use Wecoders\EnergyBundle\Entity\Brand;

class Client extends ListSearcher implements EntityListSearcherInterface
{
    private $entity = 'GCRM\CRMBundle\Entity\Client';

    protected $exporterTableName = 'client';

    private $twigTemplate = '@GCRMCRMBundle/Default/parts/listSearch/client.html.twig';

    protected $joinTables = [
        [
            'entity' => 'GCRM\CRMBundle\Entity\ClientAndContractGas',
            'as' => 'lcgas',
            'condition' => 'entity.id = lcgas.client',
        ],
        [
            'entity' => 'GCRM\CRMBundle\Entity\ContractGas',
            'as' => 'cgas',
            'condition' => 'lcgas.contract = cgas.id',
        ],
        [
            'entity' => 'GCRM\CRMBundle\Entity\ClientAndContractEnergy',
            'as' => 'lcenergy',
            'condition' => 'entity.id = lcenergy.client',
        ],
        [
            'entity' => 'GCRM\CRMBundle\Entity\ContractEnergy',
            'as' => 'cenergy',
            'condition' => 'lcenergy.contract = cenergy.id',
        ],
        [
            'entity' => 'GCRM\CRMBundle\Entity\ContractGasAndPpCode',
            'as' => 'cgappc',
            'condition' => 'cgappc.contract = cgas.id',
        ],
        [
            'entity' => 'GCRM\CRMBundle\Entity\ContractEnergyAndPpCode',
            'as' => 'ceappc',
            'condition' => 'ceappc.contract = cenergy.id',
        ],
        [
            'entity' => 'GCRM\CRMBundle\Entity\User',
            'as' => 'juser',
            'condition' => '(
            cgas.salesRepresentative = juser.id OR 
            cenergy.salesRepresentative = juser.id',
        ],
        [
            'entity' => 'GCRM\CRMBundle\Entity\UserAndBranch',
            'as' => 'juserAndBranch',
            'condition' => 'juserAndBranch.user = juser.id'
        ],
        [
            'entity' => 'GCRM\CRMBundle\Entity\AccountNumberIdentifier',
            'as' => 'ani',
            'condition' => 'entity.accountNumberIdentifier = ani.id'
        ],
    ];

    public function __construct(Request $request, ContainerInterface $container)
    {
        $this->request = $request;
        $this->container = $container;
        $this->user = $container->get('security.token_storage')->getToken()->getUser();

        $branches = [];
        $userAndBranches = $this->user->getUserAndBranches();
        foreach ($userAndBranches as $userAndBranch) {
            $branch = $userAndBranch->getBranch();
            if (!$branch) {
                continue;
            }
            $branches[] = $branch;
        }

        // user can have more branches than are set somehow, to avoid that choose branches from db
        if (count($branches)) {
            $branches = $container->get('doctrine.orm.entity_manager')->getRepository('GCRMCRMBundle:Branch')->findBy(['id' => $branches]);
        }

        $this->userBranches = $branches;
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
        if ($request->query->has('lsBrand') && $request->query->get('lsBrand')) {
            $queryBuilder->setParameter('cBrand', $request->query->get('lsBrand'));
        }
        if ($request->query->get('lsSalesRepresentative')) {
            $queryBuilder->setParameter('cSalesRepresentative', $request->query->get('lsSalesRepresentative'));
        }

        $ids = [];
        foreach ($this->userBranches as $userBranch) {
            $ids[] = $userBranch->getId();
        }
        if ($request->query->get('lsBranch')) {
            $ids = in_array($request->query->get('lsBranch'), $ids) ? [$request->query->get('lsBranch')] : $ids;
        }
        $queryBuilder->setParameter('jBranch', $ids);

        if ($request->query->get('lsSignDateFrom')) {
            $queryBuilder->setParameter('cSignDateFrom', $request->query->get('lsSignDateFrom'));
        }
        if ($request->query->get('lsSignDateTo')) {
            $queryBuilder->setParameter('cSignDateTo', $request->query->get('lsSignDateTo'));
        }
        if ($request->query->get('lsCreatedDateFrom')) {
            $queryBuilder->setParameter('cCreatedDateFrom', $request->query->get('lsCreatedDateFrom'));
        }
        if ($request->query->get('lsCreatedDateTo')) {
            $queryBuilder->setParameter('cCreatedDateTo', $request->query->get('lsCreatedDateTo'));
        }
        if ($request->query->get('lsPesel')) {
            $queryBuilder->setParameter('entityPesel', '%' . $request->query->get('lsPesel') . '%');
        }
        if ($request->query->get('lsTelephoneNr')) {
            $queryBuilder->setParameter('entityTelephoneNr', '%' . $request->query->get('lsTelephoneNr') . '%');
        }
        if ($request->query->get('lsName')) {
            $queryBuilder->setParameter('entityName', '%' . $request->query->get('lsName') . '%');
        }
        if ($request->query->get('lsSurname')) {
            $queryBuilder->setParameter('entitySurname', '%' . $request->query->get('lsSurname') . '%');
        }
        if ($request->query->get('lsNip')) {
            $queryBuilder->setParameter('entityNip', '%' . $request->query->get('lsNip') . '%');
        }
        if ($request->query->get('lsBadgeId')) {
            $queryBuilder->setParameter('entityBadgeId', '%' . $request->query->get('lsBadgeId') . '%');
        }
        if ($request->query->get('lsContractNumber')) {
            $queryBuilder->setParameter('cContractNumber', $request->query->get('lsContractNumber'));
        }
        if ($request->query->get('lsContractType')) {
            $queryBuilder->setParameter('cContractType', $request->query->get('lsContractType'));
        }
        if ($request->query->get('ppCode')) {
            $queryBuilder->setParameter('ppCode', '%' . $request->query->get('ppCode') . '%' );
        }

    }

    public function addFields(FormBuilder $builder, $options, EntityManager $em)
    {
        $lsSignDateFrom = isset($options['data']['lsSignDateFrom']) && $options['data']['lsSignDateFrom'] ? \DateTime::createFromFormat('Y-m-d', $options['data']['lsSignDateFrom']): null;
        $lsSignDateTo = isset($options['data']['lsSignDateTo']) && $options['data']['lsSignDateTo'] ? \DateTime::createFromFormat('Y-m-d', $options['data']['lsSignDateTo']): null;
        $lsCreatedDateFrom = isset($options['data']['lsCreatedDateFrom']) && $options['data']['lsCreatedDateFrom'] ? \DateTime::createFromFormat('Y-m-d', $options['data']['lsCreatedDateFrom']): null;
        $lsCreatedDateTo = isset($options['data']['lsCreatedDateTo']) && $options['data']['lsCreatedDateTo'] ? \DateTime::createFromFormat('Y-m-d', $options['data']['lsCreatedDateTo']): null;

        $agents = $em->getRepository('GCRMCRMBundle:User')->findBy([
            'isSalesRepresentative' => true
        ]);



        $activeBranch = null;
        if ($this->userBranches && isset($options['data']['lsBranch']) && $options['data']['lsBranch']) {
            foreach ($this->userBranches as $branch) {
                if ($branch->getId() == $options['data']['lsBranch']) {
                    $activeBranch = $branch;
                    break;
                }
            }
        }

        $statusDepartments = $em->getRepository('GCRMCRMBundle:StatusDepartment')->findAll();
        $activeStatusDepartment = $this->activeStatusDepartment($statusDepartments);
        $activeStatusDepartmentStatus = $this->activeStatusDepartmentStatus($statusDepartments);


        $statusContracts = $em->getRepository('GCRMCRMBundle:StatusContract')->findAll();
        $lsStatusContract = null;
        if (isset($options['data']['lsStatusContract'])) {
            /** @var StatusContract $statusContract */
            foreach ($statusContracts as $statusContract) {
                if ($statusContract->getId() == $options['data']['lsStatusContract']) {
                    $lsStatusContract = $statusContract;
                    break;
                }
            }
        }

        $lsActualStatus = null;
        if (isset($options['data']['lsActualStatus'])) {
            /** @var StatusContract $statusContract */
            foreach ($statusContracts as $statusContract) {
                if ($statusContract->getId() == $options['data']['lsActualStatus']) {
                    $lsActualStatus = $statusContract;
                    break;
                }
            }
        }


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
                'choices' => $agents,
                'choice_value' => function ($entity = null) {
                    return $entity ? $entity->getId() : '';
                },
                'choice_label' => function ($entity = null) {
                    return $entity ?: '';
                },
                'placeholder' => 'Wybierz...',
            ])
            ->add('lsBranch', ChoiceType::class, [
                'label' => 'Biuro',
                'choices' => $this->userBranches,
                'choice_value' => function ($entity = null) {
                    return $entity && is_object($entity) ? $entity->getId() : '';
                },
                'choice_label' => function ($entity = null) {
                    return $entity ?: '';
                },
                'placeholder' => 'Wybierz...',
                'data' => $activeBranch,
            ])
            ->add('lsStatusDepartment', ChoiceType::class, [
                'label' => 'Aktualny departament',
                'choices' => $statusDepartments,
                'choice_value' => function ($entity = null) {
                    return is_object($entity) ? $entity->getId() : '';
                },
                'choice_label' => function ($entity = null) {
                    return $entity ?: '';
                },
                'placeholder' => 'Wszystkie',
                'data' => $activeStatusDepartment,
            ])
            ->add('lsActualStatus', ChoiceType::class, [
                'label' => 'Aktualny status',
                'choices' => $statusContracts,
                'choice_value' => function ($entity = null) {
                    return is_object($entity) ? $entity->getId() : '';
                },
                'choice_label' => function ($entity = null) {
                    return $entity ?: '';
                },
                'placeholder' => 'Wszystkie',
                'data' => $lsActualStatus,
            ])
            ->add('lsStatusDepartmentStatus', ChoiceType::class, [
                'label' => 'Departament statusu',
                'choices' => $statusDepartments,
                'choice_value' => function ($entity = null) {
                    return is_object($entity) ? $entity->getId() : '';
                },
                'choice_label' => function ($entity = null) {
                    return $entity ?: '';
                },
                'placeholder' => 'Wszystkie',
                'data' => $activeStatusDepartmentStatus,
            ])
            ->add('lsStatusContract', ChoiceType::class, [
                'label' => 'Status departamentu',
                'choices' => $statusContracts,
                'choice_value' => function ($entity = null) {
                    return is_object($entity) ? $entity->getId() : '';
                },
                'choice_label' => function ($entity = null) {
                    return $entity ?: '';
                },
                'placeholder' => 'Wszystkie',
                'data' => $lsStatusContract,
            ])
            ->add('lsPesel', TextType::class, [
                'label' => 'PESEL',
            ])
            ->add('lsTelephoneNr', TextType::class, [
                'label' => 'Numer telefonu',
            ])
            ->add('lsSignDateFrom', DateType::class, [
                'label' => 'Data podpisania od',
                'data' => $lsSignDateFrom,
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'datepicker'
                ]
            ])
            ->add('lsSignDateTo', DateType::class, [
                'label' => 'do',
                'data' => $lsSignDateTo,
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'datepicker'
                ]
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
            ->add('lsName', TextType::class, [
                'label' => 'Imię',
            ])
            ->add('lsSurname', TextType::class, [
                'label' => 'Nazwisko',
            ])
            ->add('lsContractNumber', TextType::class, [
                'label' => 'Numer umowy',
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
                'label' => 'Data wystawienia faktury od',
                'disabled' => true,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'datepicker'
                ]
            ])
            ->add('lsInvoiceCreatedDateTo', DateType::class, [
                'label' => 'do',
                'disabled' => true,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'datepicker'
                ]
            ])
            ->add('lsInvoiceNumber', TextType::class, [
                'label' => 'Numer faktury',
                'disabled' => true,
            ])
            ->add('ppCode', TextType::class, [
                'label' => 'Kod PP',
            ])
        ;

        $brands = $em->getRepository('WecodersEnergyBundle:Brand')->findAll();

        $lsBrand = null;
        if (isset($options['data']['lsBrand'])) {
            /** @var Brand $brand */
            foreach ($brands as $brand) {
                if ($brand->getId() == $options['data']['lsBrand']) {
                    $lsBrand = $brand;
                    break;
                }
            }
        }

        $builder->add('lsBrand', ChoiceType::class, [
            'label' => 'Marka',
            'choices' => $brands,
            'choice_value' => function ($entity = null) {
                return $entity && is_object($entity) ? $entity->getId() : '';
            },
            'choice_label' => function ($entity = null) {
                return $entity ?: '';
            },
            'placeholder' => 'Wybierz...',
            'data' => $lsBrand,
        ]);
    }

    private function activeStatusDepartment($statusDepartments)
    {
        // select choosen
        $statusDepartmentChoosenId = $this->request->query->has('lsStatusDepartment') ? $this->request->query->get('lsStatusDepartment') : null;
        if ($statusDepartmentChoosenId) {
            /** @var StatusDepartment $statusDepartment */
            foreach ($statusDepartments as $statusDepartment) {
                if ($statusDepartment->getId() == $statusDepartmentChoosenId) {
                    return $statusDepartment;
                }
            }
        }

        // native filter
        $statusDepartmentChoosenCode = $this->request->query->has('statusDepartment') ? $this->request->query->get('statusDepartment') : null;
        if ($statusDepartmentChoosenCode) {
            /** @var StatusDepartment $statusDepartment */
            foreach ($statusDepartments as $statusDepartment) {
                if ($statusDepartment->getCode() == $statusDepartmentChoosenCode) {
                    return $statusDepartment;
                }
            }
        }

        return null;
    }

    private function activeStatusDepartmentStatus($statusDepartments)
    {
        // select choosen
        $statusDepartmentChoosenId = $this->request->query->has('lsStatusDepartmentStatus') ? $this->request->query->get('lsStatusDepartmentStatus') : null;
        if ($statusDepartmentChoosenId) {
            /** @var StatusDepartment $statusDepartment */
            foreach ($statusDepartments as $statusDepartment) {
                if ($statusDepartment->getId() == $statusDepartmentChoosenId) {
                    return $statusDepartment;
                }
            }
        }

        return null;
    }

    public function addQuery(Request $request, &$dqlAnd = [], &$dqlOr = [], $statusDepartments)
    {
        // extra filter
        // hides all resignations and broken contracts
        if ($request->query->has('lsHideNotActual') && $request->query->get('lsHideNotActual')) {
            $tempOr = [
                ' (cgas.isResignation != 1) ',
                ' (cenergy.isResignation != 1) ',
            ];

            $tempOrQueryPart = implode(' OR ', $tempOr);

            $dqlAnd[] = '(' . $tempOrQueryPart . ')';

            $tempOr = [
                ' (cgas.isBrokenContract != 1) ',
                ' (cenergy.isBrokenContract != 1) ',
            ];

            $tempOrQueryPart = implode(' OR ', $tempOr);

            $dqlAnd[] = '(' . $tempOrQueryPart . ')';
        }

        if ($request->query->has('lsBrand') && $request->query->get('lsBrand')) {
            $tempOr = [
                ' (cgas.brand = :cBrand) ',
                ' (cenergy.brand= :cBrand) ',
            ];

            $tempOrQueryPart = implode(' OR ', $tempOr);

            $dqlAnd[] = '(' . $tempOrQueryPart . ')';
        }

        if ($request->query->get('lsSalesRepresentative')) {
            $tempOr = [
                ' (cgas.salesRepresentative = :cSalesRepresentative) ',
                ' (cenergy.salesRepresentative = :cSalesRepresentative) ',
            ];

            $tempOrQueryPart = implode(' OR ', $tempOr);

            $dqlAnd[] = '(' . $tempOrQueryPart . ')';
        }

        if ($request->query->get('lsBranch')) {
            $tempOr = [
                ' juserAndBranch.branch = :jBranch ',
            ];

            $tempOrQueryPart = implode(' OR ', $tempOr);

            $dqlAnd[] = '(' . $tempOrQueryPart . ')';
        } else {
            // lsBranch is not choosen so show contracts from all branches of that user
            $dqlAnd[] = '(juserAndBranch.branch IN (:jBranch))';
        }

        if ($request->query->get('lsContractNumber')) {
            $tempOr = [
                ' (cgas.contractNumber LIKE :cContractNumber) ',
                ' (cenergy.contractNumber LIKE :cContractNumber) ',
            ];

            $tempOrQueryPart = implode(' OR ', $tempOr);

            $dqlAnd[] = '(' . $tempOrQueryPart . ')';
        }

        if ($request->query->get('lsContractType')) {
            $tempOr = [
                ' (cgas.type = :cContractType) ',
                ' (cenergy.type = :cContractType) ',
            ];

            $tempOrQueryPart = implode(' OR ', $tempOr);

            $dqlAnd[] = '(' . $tempOrQueryPart . ')';
        }


        $tempOr = null;
        $lsSignDateFrom = $request->query->get('lsSignDateFrom');
        $lsSignDateTo = $request->query->get('lsSignDateTo');
        if ($lsSignDateFrom && $lsSignDateTo) {
            $tempOr = [
                ' (cgas.signDate >= :cSignDateFrom AND cgas.signDate <= :cSignDateTo) ',
                ' (cenergy.signDate >= :cSignDateFrom AND cenergy.signDate <= :cSignDateTo) ',
            ];
        } elseif ($lsSignDateFrom) {
            $tempOr = [
                ' cgas.signDate >= :cSignDateFrom ',
                ' cenergy.signDate >= :cSignDateFrom ',
            ];
        } elseif ($lsSignDateTo) {
            $tempOr = [
                ' cgas.signDate <= :cSignDateTo ',
                ' cenergy.signDate <= :cSignDateTo ',
            ];
        }
        if ($tempOr && is_array($tempOr)) {
            $tempOrQueryPart = implode(' OR ', $tempOr);
            $dqlAnd[] = '(' . $tempOrQueryPart . ')';
        }



        $tempOr = null;
        $lsCreatedDateFrom = $request->query->get('lsCreatedDateFrom');
        $lsCreatedDateTo = $request->query->get('lsCreatedDateTo');
        if ($lsCreatedDateFrom && $lsCreatedDateTo) {
            $tempOr = [
                ' (cgas.createdAt >= :cCreatedDateFrom AND cgas.createdAt <= :cCreatedDateTo) ',
                ' (cenergy.createdAt >= :cCreatedDateFrom AND cenergy.createdAt <= :cCreatedDateTo) ',
            ];
        } elseif ($lsCreatedDateFrom) {
            $tempOr = [
                ' cgas.createdAt >= :cCreatedDateFrom ',
                ' cenergy.createdAt >= :cCreatedDateFrom ',
            ];
        } elseif ($lsCreatedDateTo) {
            $tempOr = [
                ' cgas.createdAt <= :cCreatedDateTo ',
                ' cenergy.createdAt <= :cCreatedDateTo ',
            ];
        }
        if ($tempOr && is_array($tempOr)) {
            $tempOrQueryPart = implode(' OR ', $tempOr);
            $dqlAnd[] = '(' . $tempOrQueryPart . ')';
        }


        if ($request->query->get('lsPesel')) {
            $dqlAnd[] = ' (entity.pesel LIKE :entityPesel OR cgas.secondPersonPesel LIKE :entityPesel OR cenergy.secondPersonPesel LIKE :entityPesel)';
        }
        if ($request->query->get('lsTelephoneNr')) {
            $dqlAnd[] = ' entity.telephoneNr LIKE :entityTelephoneNr';
        }
        if ($request->query->get('lsName')) {
            $dqlAnd[] = ' (entity.name LIKE :entityName OR cgas.secondPersonName LIKE :entityName OR cenergy.secondPersonName LIKE :entityName)';
        }
        if ($request->query->get('lsSurname')) {
            $dqlAnd[] = ' (entity.surname LIKE :entitySurname OR cgas.secondPersonSurname LIKE :entitySurname OR cenergy.secondPersonSurname LIKE :entitySurname)';
        }
        if ($request->query->get('lsNip')) {
            $dqlAnd[] = ' entity.nip LIKE :entityNip';
        }
        if ($request->query->get('lsBadgeId')) {
            $dqlAnd[] = ' ani.number LIKE :entityBadgeId';
        }
        if ($request->query->get('ppCode')) {
            $dqlAnd[] = ' (cgappc.ppCode LIKE :ppCode OR ceappc.ppCode LIKE :ppCode )';
        }

        $statusDepartmentId = $request->query->get('lsStatusDepartment');
        $statusContractId = $request->query->get('lsStatusContract');
        $statusDepartmentStatusId = $request->query->get('lsStatusDepartmentStatus');
        $actualStatusId = $request->query->get('lsActualStatus');

        if ($statusDepartmentId && is_numeric($statusDepartmentId)) {
            $tempOr = [
                ' cgas.statusDepartment = ' . $statusDepartmentId . ' ',
                ' cenergy.statusDepartment = ' . $statusDepartmentId . ' ',
            ];
            if ($tempOr && is_array($tempOr)) {
                $tempOrQueryPart = implode(' OR ', $tempOr);
                $dqlAnd[] = '(' . $tempOrQueryPart . ')';
            }
        }

        if ($actualStatusId && is_numeric($actualStatusId)) {
            $tempOr = [
                ' cgas.actualStatus = ' . $actualStatusId . ' ',
                ' cenergy.actualStatus = ' . $actualStatusId . ' ',
            ];
            if ($tempOr && is_array($tempOr)) {
                $tempOrQueryPart = implode(' OR ', $tempOr);
                $dqlAnd[] = '(' . $tempOrQueryPart . ')';
            }
        }

        if ($statusDepartmentStatusId && is_numeric($statusDepartmentStatusId) && $statusContractId && is_numeric($statusContractId)) {
            $contractVariableName = $this->statusContractVariableNameByDepartment($statusDepartments, $statusDepartmentStatusId);
            $dqlOr[] = ' cgas.statusContract' . $contractVariableName . ' = ' . $statusContractId;
            $dqlOr[] = ' cenergy.statusContract' . $contractVariableName . ' = ' . $statusContractId;
        } elseif ($statusContractId && is_numeric($statusContractId)) {
            $dqlOr[] = $this->statusContractQueryByContractCode('cgas', $statusContractId);
            $dqlOr[] = $this->statusContractQueryByContractCode('cenergy', $statusContractId);
        }
    }

    private function statusContractQueryByContractCode($contractCode, $statusContractId)
    {
        $departments = [
            'Finances',
            'Process',
            'Control',
            'Verification',
            'Administration'
        ];

        $dqlOr = [];
        foreach ($departments as $department) {
            $dqlOr[] = ' ' . $contractCode . '.statusContract' . $department . ' = ' . $statusContractId;
        }

        return implode(' OR ', $dqlOr);
    }

    private function statusContractVariableNameByDepartment($statusDepartments, $statusDepartmentId)
    {
        $result = null;

        /** @var StatusDepartment $department */
        foreach ($statusDepartments as $department) {
            if ($department->getId() == $statusDepartmentId) {
                /** @var StatusDepartment $result */
                $result = $department;
            }
        }

        if (!$result) {
            die('Wybrany departament nie istnieje');
        }

        $code = $result->getCode();
        $variableName = null;
        if ($code == 'finances') {
            $variableName = 'Finances';
        } elseif ($code == 'process') {
            $variableName = 'Process';
        } elseif ($code == 'control') {
            $variableName = 'Control';
        } elseif ($code == 'verification') {
            $variableName = 'Verification';
        } elseif ($code == 'administration') {
            $variableName = 'Administration';
        }

        if (!$variableName) {
            die('Statusy działów są błędnie zdefiniowane');
        }

        return $variableName;
    }

    public function applyFilterListSearch(Request $request, ContractInterface $contract)
    {
        return $contract;
    }

}