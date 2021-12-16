<?php

namespace GCRM\CRMBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContractAndTariffGroupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('tariff', EntityType::class, [
                'class' => $options['tariff_class'],
                'label' => 'Grupa taryfowa',
                'placeholder' => 'Wybierz taryfę...',
            ])
            ->add('fromDate', DateType::class, [
                'label' => 'Ważna od',
                'widget' => 'single_text',
                'attr' => ['class' => 'datepicker']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'tariff_class' => null,
        ));
    }
}
