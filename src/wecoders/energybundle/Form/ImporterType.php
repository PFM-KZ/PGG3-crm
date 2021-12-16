<?php

namespace Wecoders\EnergyBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImporterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('operator', ChoiceType::class, [
                'label' => '',
                'placeholder' => 'Wybierz ...',
                'choices' => array_flip($options['data']),
            ])
            ->add('files', FileType::class, [
                'label' => 'Odczyt/y',
                'multiple' => true
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
