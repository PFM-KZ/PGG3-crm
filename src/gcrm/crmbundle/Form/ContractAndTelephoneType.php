<?php

namespace GCRM\CRMBundle\Form;

use GCRM\CRMBundle\Entity\ContractAndTelephone;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContractAndTelephoneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('telephone', TextType::class, [
                'label' => 'Telefon',
            ])
            ->add('contractAndTelephoneWithPackages', CollectionType::class, [
                'label' => 'Pakiety',
                'entry_type' => 'GCRM\CRMBundle\Form\ContractAndTelephoneWithPackageType',
                'by_reference' => false,
                'allow_add' => true,
                'allow_delete' => true
            ])
            ->add('contractAndTelephoneWithServices', CollectionType::class, [
                'label' => 'Usługi',
                'entry_type' => 'GCRM\CRMBundle\Form\ContractAndTelephoneWithServiceType',
                'by_reference' => false,
                'allow_add' => true,
                'allow_delete' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ContractAndTelephone::class,
        ));
    }
}
