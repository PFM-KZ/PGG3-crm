<?php

namespace GCRM\CRMBundle\Form;

use GCRM\CRMBundle\Service\StatisticsModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StatisticsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = $options['data'] ? $options['data'] : null;

        $yearStartDate = new \DateTime();
        $yearStartDate->setDate($yearStartDate->format('Y'), 1, 1);

        $builder
            ->add('dateFrom', DateType::class, [
                'label' => 'Od',
                'mapped' => false,
                'data' => $yearStartDate,
            ])
            ->add('dateTo', DateType::class, [
                'label' => 'do',
                'mapped' => false,
                'data' => new \DateTime(),
            ])
            ->add('options', ChoiceType::class, [
                'label' => 'Opcje',
                'choices' => $choices,
                'placeholder' => 'Wybierz opcję...',
                'mapped' => false,
            ])
            ->add('calculate', SubmitType::class, ['label' => 'Sprawdź'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null,
        ));
    }
}
