<?php

namespace GCRM\CRMBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostponedDeadlinesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('isTerminationSent', ChoiceType::class, [
                'label' => 'Wysłano wypowiedzenie',
                'choices' => [
                    'Tak' => 1,
                    'Nie' => 0,
                ],
                'placeholder' => 'Wybierz...'
            ])
            ->add('terminationCreatedDate', DateType::class, [
                'label' => 'Data wysłania wypowiedzenia',
                'widget' => 'single_text',
                'attr' => ['class' => 'datepicker']
            ])
            ->add('isProposalOsdSent', ChoiceType::class, [
                'label' => 'Wysłano wniosek na OSD',
                'choices' => [
                    'Tak' => 1,
                    'Nie' => 0,
                ],
                'placeholder' => 'Wybierz...'
            ])
            ->add('plannedActivationDate', DateType::class, [
                'label' => 'Planowana data uruchomienia',
                'widget' => 'single_text',
                'attr' => ['class' => 'datepicker']
            ])
            ->add('proposalStatus', ChoiceType::class, [
                'label' => 'Status wniosku',
                'choices' => [
                    'Pozytywny' => 1,
                    'Negatywny' => 0
                ],
                'placeholder' => 'Wybierz...'
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