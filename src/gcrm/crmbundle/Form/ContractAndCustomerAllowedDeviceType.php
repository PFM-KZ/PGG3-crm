<?php

namespace GCRM\CRMBundle\Form;

use GCRM\CRMBundle\Entity\ContractAndCustomerAllowedDevice;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContractAndCustomerAllowedDeviceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('customerAllowedDevice', EntityType::class, [
                'class' => 'GCRMCRMBundle:CustomerAllowedDevice',
                'label' => 'Urządzenie',
            ])
            ->add('description', TextType::class, [
                'label' => 'Opis',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ContractAndCustomerAllowedDevice::class,
        ));
    }
}
