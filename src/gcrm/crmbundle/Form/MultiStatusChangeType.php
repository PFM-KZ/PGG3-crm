<?php

namespace GCRM\CRMBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MultiStatusChangeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('statusDepartment', EntityType::class, [
                'class' => 'GCRMCRMBundle:StatusDepartment',
                'label' => 'Departament zmiany statusu',
            ])
            ->add('statusContract', EntityType::class, [
                'class' => 'GCRMCRMBundle:StatusContract',
                'label' => 'Nowy status',
                'required' => false,
            ])
            ->add('file', FileType::class, [
                'label' => 'Plik',
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Zapisz',
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
