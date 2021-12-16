<?php

namespace GCRM\CRMBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddContractType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('contractTypes', EntityType::class, array(
                'label' => 'Dodaj umowę',
                'expanded' => true,
                'multiple' => true,
                'class' => 'GCRMCRMBundle:ContractType',
            ))
            ->add('save', SubmitType::class, ['label' => 'Dodaj'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null,
        ));
    }
}
