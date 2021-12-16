<?php

namespace Wecoders\EnergyBundle\Service\ListSearcher;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\Request;
use GCRM\CRMBundle\Service\ListSearcher\ListSearcher;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GCRM\CRMBundle\Service\ListSearcher\EntityListSearcherInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Wecoders\EnergyBundle\Entity\SmsMessage as EntitySmsMessage;

class SmsMessage extends ListSearcher implements EntityListSearcherInterface
{
    private $entity = 'Wecoders\EnergyBundle\Entity\SmsMessage';
    private $twigTemplate = '@WecodersEnergyBundle/default/parts/listSearch/sms-client-group.html.twig';
    private $smsClientGroupRepository;

    protected $joinTables = [
        [
            'entity' => 'Wecoders\EnergyBundle\Entity\SmsClientGroup',
            'as' => 'scg',
            'condition' => 'entity.smsClientGroup = scg'
        ],
        [
            'entity' => 'GCRM\CRMBundle\Entity\Client',
            'as' => 'c',
            'condition' => 'entity.client = c',
        ]
    ];

    public function __construct(Request $request, ContainerInterface $container)
    {
        $this->request = $request;
        $this->container = $container;
        $this->smsClientGroupRepository = $container->get('wecoders.energybundle.sms_client_group_repository');
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
        if ($request->query->get('name')) {
            $queryBuilder->setParameter('client_name', $request->query->get('name'));
        }

        if ($request->query->get('surname')) {
            $queryBuilder->setParameter('client_surname', $request->query->get('surname'));
        }

        if ($request->query->get('telephoneNr')) {
            $queryBuilder->setParameter('client_telephone_nr', $request->query->get('telephoneNr'));
        }

        if ($request->query->get('badgeId')) {
            $queryBuilder->setParameter('client_badge_id', $request->query->get('badgeId'));
        }

        if ($request->query->get('smsClientGroup')) {
            $queryBuilder->setParameter('smsClientGroup', $request->query->get('smsClientGroup'));
        }

        if ($request->query->get('status') !== null && 'all' != $request->query->get('status')) {
            $queryBuilder->setParameter('status', $request->query->get('status'));
        }

        if ($request->query->get('createdAt')) {
            $day = new \DateTime($request->query->get('createdAt'));
            $nextDay = (clone $day)->modify('+1days');
            $queryBuilder->setParameter('dayCreatedAt', $day);
            $queryBuilder->setParameter('nextDayCreatedAt', $nextDay);
        }

        if ($request->query->get('sentAt')) {
            $day = new \DateTime($request->query->get('sentAt'));
            $nextDay = (clone $day)->modify('+1days');
            $queryBuilder->setParameter('daySentAt', $day);
            $queryBuilder->setParameter('nextDaySentAt', $nextDay);
        }
    }

    public function addFields(FormBuilder $builder, $options, EntityManager $em)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) use ($em) {
            $data = $event->getData();

            $data['createdAt'] = isset($data['createdAt']) ? ($data['createdAt'] ? new \DateTime($data['createdAt']) : null) : null;
            $data['sentAt'] = isset($data['sentAt']) ? ($data['sentAt'] ? new \DateTime($data['sentAt']) : null) : null;
            $data['status'] = isset($data['status']) ? ($data['status'] !== null ? $data['status'] : 'all') : 'all';
            
            if (isset($data['smsClientGroup']) && $data['smsClientGroup']) {
                $data['smsClientGroup'] = $this->smsClientGroupRepository->find($data['smsClientGroup']);
            } else {
                $data['smsClientGroup'] = null;
            }
            

            $event->setData($data);
        });
    
        $builder
            ->add('name', TextType::class, [
                'label' => 'Imię',
            ])
            ->add('surname', TextType::class, [
                'label' => 'Nazwisko',
            ])
            ->add('telephoneNr', TextType::class, [
                'label' => 'Numer telefonu'
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Wszystkie' => 'all',
                    'W kolejce' => EntitySmsMessage::STATUS_AWAITING,
                    'Error' => EntitySmsMessage::STATUS_ERROR,
                    'Wysłano' => EntitySmsMessage::STATUS_SUCCESS,
                ],
                'attr' => [
                    'data-widget' => 'select2'
                ]
            ])
            ->add('badgeId', TextType::class, [
                'label' => 'Nr. rachunku',
            ])
            ->add('smsClientGroup', EntityType::class, [
                'label' => 'Grupa wysyłkowa',
                'class' => 'Wecoders\EnergyBundle\Entity\SmsClientGroup',
                'choices' => array_merge([null], $this->smsClientGroupRepository->findAll()),
                'choice_label' => function($choice) {
                    return $choice ? $choice : 'Wszystkie';
                },
                'attr' => [
                    'data-widget' => 'select2',
                ],
            ])
            ->add('createdAt', DateType::class, [
                'label' => 'Data utworzenia',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'datepicker'
                ]
            ])
            ->add('sentAt', DateType::class, [
                'label' => 'Data wysłania',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'datepicker'
                ]
            ])
        ;
    }

    public function addQuery(Request $request, &$dqlAnd = [], &$dqlOr = [], $statusDepartments)
    {
        if ($request->query->get('name')) {
            $dqlAnd[] = ' c.name = :client_name';
        }
        if ($request->query->get('surname')) {
            $dqlAnd[] = ' c.surname = :client_surname';
        }
        if ($request->query->get('badgeId')) {
            $dqlAnd[] = 'c.badgeId = :client_badge_id';
        }

        if ($request->query->get('status') !== null && 'all' != $request->query->get('status')) {
            $dqlAnd[] = 'entity.statusCode = :status';
        }

        if ($request->query->get('telephoneNr')) {
            $dqlAnd[] = '(c.telephoneNr = :client_telephone_nr OR c.contactTelephoneNr = :client_telephone_nr OR entity.number = :client_telephone_nr)';
        }

        if ($request->query->get('smsClientGroup')) {
            $dqlAnd[] = 'scg.id = :smsClientGroup';
        }

        if ($request->query->get('createdAt')) {
            $dqlAnd[] = 'entity.createdAt >= :dayCreatedAt AND entity.createdAt < :nextDayCreatedAt';
        }

        if ($request->query->get('sentAt')) {
            $dqlAnd[] = 'entity.sentAt >= :daySentAt AND entity.sentAt < :nextDaySentAt';
        }
    }
}