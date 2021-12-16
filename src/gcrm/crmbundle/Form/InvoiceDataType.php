<?php

namespace GCRM\CRMBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvoiceDataType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', IntegerType::class, [
                'label' => 'ID',
                'disabled' => true
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Telefon',
            ])
            ->add('services', CollectionType::class, [
                'label' => 'Usługi',
                'entry_type' => 'GCRM\CRMBundle\Form\InvoiceServiceType',
                'by_reference' => false,
                'allow_add' => true,
                'allow_delete' => true
            ])
            ->add('rabates', CollectionType::class, [
                'label' => 'Rabaty',
                'entry_type' => 'GCRM\CRMBundle\Form\InvoiceRabateType',
                'by_reference' => false,
                'allow_add' => true,
                'allow_delete' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null,
        ));
    }
}
