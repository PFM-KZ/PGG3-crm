<?php

namespace GCRM\CRMBundle\Form;

use GCRM\CRMBundle\Entity\ContractAndTelephoneWithPackage;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContractAndTelephoneWithPackageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('package', EntityType::class, [
                'class' => 'GCRMCRMBundle:Package',
                'label' => 'Pakiet',
            ])
            ->add('durationInMonths', TextType::class, [
                'label' => 'Okres (miesiące)',
            ])
            ->add('activationDate', DateType::class, [
                'label' => 'Data aktywacji',
            ])
            ->add('usedSeconds', TextType::class, [
                'label' => 'Wykorzystany czas (sekundy)',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ContractAndTelephoneWithPackage::class,
        ));
    }
}
