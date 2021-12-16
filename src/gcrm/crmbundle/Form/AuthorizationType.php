<?php

namespace GCRM\CRMBundle\Form;

use GCRM\CRMBundle\Entity\StatusContract;
use GCRM\CRMBundle\Service\StatusContractModel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class AuthorizationType extends AbstractType
{
    private $container;
    private $statusContractModel;

    public function __construct(ContainerInterface $container, StatusContractModel $statusContractModel)
    {
        $this->container = $container;
        $this->statusContractModel = $statusContractModel;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $statusContractFromAuthorization = $this->statusContractModel->getAllStatusContractFromAuthorization();

        $builder
            ->add('telephoneCallNumber', TextType::class, [
                'label' => 'Numer telefonu',
                'constraints' => [
                    new Regex('/^[0-9]{9}$/')
                ],
                'required' => false,
            ])
            ->add('name', TextType::class, [
                'label' => 'Imię',
                'constraints' => [
                    new NotBlank()
                ]
            ])
            ->add('surname', TextType::class, [
                'label' => 'Nazwisko',
                'constraints' => [
                    new NotBlank()
                ]
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
            ->add('contractTypes', EntityType::class, array(
                'label' => 'Wybierz umowy',
                'expanded' => true,
                'multiple' => false,
                'class' => 'GCRMCRMBundle:ContractType',
            ))
            ->add('save', SubmitType::class, ['label' => 'Zapisz'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null,
        ));
    }
}
