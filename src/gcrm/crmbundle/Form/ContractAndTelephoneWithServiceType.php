<?php

namespace GCRM\CRMBundle\Form;

use Doctrine\DBAL\Types\BooleanType;
use GCRM\CRMBundle\Entity\ContractAndTelephoneWithService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContractAndTelephoneWithServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('service', EntityType::class, [
                'class' => 'GCRMCRMBundle:Service',
                'label' => 'Usługa',
            ])
            ->add('durationInMonths', TextType::class, [
                'label' => 'Okres (miesiące)',
            ])
            ->add('activationDate', DateType::class, [
                'label' => 'Data aktywacji',
            ])
            ->add('isPartialBilling', null, [
                'label' => 'Rozliczanie częściowe',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ContractAndTelephoneWithService::class,
        ));
    }
}
