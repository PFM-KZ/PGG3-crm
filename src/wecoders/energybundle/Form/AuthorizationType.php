<?php

namespace Wecoders\EnergyBundle\Form;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\ContractType;
use GCRM\CRMBundle\Entity\StatusContract;
use GCRM\CRMBundle\Entity\User;
use GCRM\CRMBundle\Service\StatusContractModel;
use GCRM\CRMBundle\Service\UserModel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Wecoders\EnergyBundle\Entity\Brand;
use Wecoders\EnergyBundle\Entity\PriceList;
use Wecoders\EnergyBundle\Repository\TariffRepository;
use Wecoders\EnergyBundle\Service\BrandModel;
use Wecoders\EnergyBundle\Service\EnergyTypeModel;
use Wecoders\EnergyBundle\Service\PriceListModel;

class AuthorizationType extends AbstractType
{
    const PERSON_TYPE_PERSON = 1;
    const PERSON_TYPE_COMPANY = 2;

    private $em;
    private $container;
    private $statusContractModel;
    private $priceListModel;
    private $userModel;
    private $brandModel;
    private $brand;

    public function __construct(
        EntityManager $em,
        ContainerInterface $container,
        StatusContractModel $statusContractModel,
        PriceListModel $priceListModel,
        UserModel $userModel,
        BrandModel $brandModel,
        \GCRM\CRMBundle\Service\Settings\Brand $brand
    )
    {
        $this->em = $em;
        $this->container = $container;
        $this->statusContractModel = $statusContractModel;
        $this->priceListModel = $priceListModel;
        $this->userModel = $userModel;
        $this->brandModel = $brandModel;
        $this->brand = $brand;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $contractTypes = $this->em->getRepository('GCRMCRMBundle:ContractType')->findAll();
        $statusContractFromAuthorization = $this->statusContractModel->getAllStatusContractFromAuthorization();
        $salesRepresentative = $this->userModel->getSalesRepresentativeUsers();

        $builder
            ->add('personType', ChoiceType::class, [
                'label' => 'Osoba fizyczna / firma',
                'required' => true,
                'expanded' => true,
                'multiple' => false,
                'choices' => array_flip([
                    1 => 'Osoba fizyczna',
                    2 => 'Firma',
                ]),
            ])
            ->add('datepicker', DateType::class, [
                'label' => 'Data podpisania',
                'widget' => 'single_text',
                'mapped' => false,
            ])
            ->add('salesRepresentative', ChoiceType::class, array(
                'label' => 'Pełnomocnik',
                'required' => true,
                'mapped' => false,
                'choices' => $salesRepresentative,
                'choice_value' => function (User $entity = null) {
                    return $entity ? $entity->getId() : '';
                },
                'choice_label' => function (User $entity = null) {
                    return $entity ? $entity->getName() . ' ' . $entity->getSurname() . ' ID: ' . $entity->getProxyNumber() : '';
                },
                'placeholder' => 'Wybierz...',
            ))
            ->add('contractNumber', TextType::class, [
                'label' => 'Numer umowy',
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('consumption', NumberType::class, [
                'label' => 'Zużycie na umowie (kWh)',
                'constraints' => [
                    new NotBlank(),
                ]
            ])
            ->add('contractPeriodInMonths', ChoiceType::class, [
                'label' => 'Okres umowy (mc)',
                'constraints' => [
                    new NotBlank()
                ],
                'choices' => [
                    6 => 6,
                    12 => 12,
                    24 => 24,
                    36 => 36,
                    48 => 48,
                    60 => 60
                ]
            ])
            ->add('telephoneCallNumber', TextType::class, [
                'label' => 'Numer telefonu',
                'constraints' => [
                    new Regex('/^[0-9]{9}$/')
                ],
                'required' => false,
            ])
            ->add('email', TextType::class, [
                'label' => 'Adres e-mail',
                'required' => false,
            ])
            ->add('name', TextType::class, [
                'label' => 'Imię',
            ])
            ->add('surname', TextType::class, [
                'label' => 'Nazwisko',
            ])
            ->add('pesel', TextType::class, [
                'label' => 'Pesel',
                'constraints' => [
                    new Regex('/^[0-9]{11}$/')
                ]
            ])
            ->add('statusAuthorization', ChoiceType::class, array(
                'label' => 'Status',
                'expanded' => true,
                'multiple' => false,
                'choices' => $statusContractFromAuthorization,
                'choice_value' => function (StatusContract $entity = null) {
                    return $entity ? $entity->getId() : '';
                },
                'choice_label' => function (StatusContract $entity = null) {
                    return $entity ? $entity->getTitle() : '';
                },
            ))
            ->add('commentAuthorization', TextareaType::class, [
                'label' => 'Dodatkowe informacje',
                'required' => false
            ])

            ->add('ppZipCode', TextType::class, [
                'label' => 'PP kod pocztowy',
                'required' => false,
            ])
            ->add('ppCity', TextType::class, [
                'label' => 'PP miasto',
                'required' => false,
            ])
            ->add('ppStreet', TextType::class, [
                'label' => 'PP ulica',
                'required' => false,
            ])
            ->add('ppHouseNr', TextType::class, [
                'label' => 'PP nr domu',
                'required' => false,
            ])
            ->add('ppApartmentNr', TextType::class, [
                'label' => 'PP nr lokalu',
                'required' => false,
            ])
            ->add('companyNip', TextType::class, [
                'label' => 'NIP',
                'required' => true,
                'constraints' => [
                    new Regex('/^[0-9]{10}$/')
                ]
            ])
            ->add('companyName', TextType::class, [
                'label' => 'Nazwa firmy',
                'required' => true,
            ])
            ->add('isContractMultiPerson', CheckboxType::class, [
                'label' => 'Umowa na 2 osoby',
                'required' => false,
            ])
            ->add('secondPersonName', TextType::class, [
                'label' => 'Imię',
            ])
            ->add('secondPersonSurname', TextType::class, [
                'label' => 'Nazwisko',
            ])
            ->add('secondPersonPesel', TextType::class, [
                'label' => 'Pesel',
                'constraints' => [
                    new Regex('/^[0-9]{11}$/')
                ],
            ])
            ->add('save', SubmitType::class, ['label' => 'Zapisz'])
        ;

        $setting = $this->brand->getRecord('is_enabled');
        if ($setting->getValue()) {
            $builder
                ->add('brands', ChoiceType::class, array(
                    'label' => 'Marka',
                    'required' => true,
                    'mapped' => false,
                    'choices' => $this->brandModel->getRecords(),
                    'choice_value' => function (Brand $entity = null) {
                        return $entity ? $entity->getId() : '';
                    },
                    'choice_label' => function (Brand $entity = null) {
                        return $entity ? $entity->getTitle() : '';
                    },
                    'placeholder' => 'Wybierz...',
                ))
            ;
        }

        if ($contractTypes && count($contractTypes) > 1) {
            $builder
                ->add('contractTypes', EntityType::class, array(
                    'label' => 'Rodzaj umowy',
                    'expanded' => true,
                    'multiple' => false,
                    'class' => 'GCRMCRMBundle:ContractType',
                ))
            ;
        }

        if ($contractTypes && count($contractTypes)) {
            /** @var ContractType $contractType */
            foreach ($contractTypes as $contractType) {
                if ($contractType->getCode() == 'energy') {
                    $builder
                        ->add('tariffEnergy', EntityType::class, array(
                            'label' => 'Grupa taryfowa',
                            'expanded' => false,
                            'multiple' => false,
                            'class' => 'WecodersEnergyBundle:Tariff',
                            'query_builder' => function (TariffRepository $repository) {
                                return $repository->filterTariffByEnergyType(EnergyTypeModel::TYPE_ENERGY);
                            },
                            'placeholder' => 'Wybierz...',
                        ))
                        ->add('priceListEnergy', ChoiceType::class, array(
                            'label' => 'Cennik',
                            'required' => true,
                            'mapped' => false,
                            'choices' => $this->priceListModel->getActivePriceLists(EnergyTypeModel::TYPE_ENERGY),
                            'choice_value' => function (PriceList $entity = null) {
                                return $entity ? $entity->getId() : '';
                            },
                            'choice_label' => function (PriceList $entity = null) {
                                return $entity ? $entity->getPriceListGroup()->getTitle() . ' ' . $entity->getTitle() : '';
                            },
                            'placeholder' => 'Wybierz...',
                        ))
                    ;
                } elseif ($contractType->getCode() == 'gas') {
                    $builder
                        ->add('tariffGas', EntityType::class, array(
                            'label' => 'Grupa taryfowa',
                            'expanded' => false,
                            'multiple' => false,
                            'class' => 'WecodersEnergyBundle:Tariff',
                            'query_builder' => function (TariffRepository $repository) {
                                return $repository->filterTariffByEnergyType(EnergyTypeModel::TYPE_GAS);
                            },
                            'placeholder' => 'Wybierz...',
                        ))
                        ->add('priceListGas', ChoiceType::class, array(
                            'label' => 'Cennik',
                            'required' => true,
                            'mapped' => false,
                            'choices' => $this->priceListModel->getActivePriceLists(EnergyTypeModel::TYPE_GAS),
                            'choice_value' => function (PriceList $entity = null) {
                                return $entity ? $entity->getId() : '';
                            },
                            'choice_label' => function (PriceList $entity = null) {
                                return $entity ? $entity->getPriceListGroup()->getTitle() . ' ' . $entity->getTitle() : '';
                            },
                            'placeholder' => 'Wybierz...',
                        ))
                    ;
                }
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null,
        ));
    }
}
