<?php

namespace Wecoders\EnergyBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvoiceConsumptionByDeviceDataType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('deviceId', TextType::class, [
                'label' => 'Numer licznika',
            ])
            ->add('tariff', TextType::class, [
                'label' => 'Taryfa',
            ])
            ->add('area', TextType::class, [
                'label' => 'Strefa',
            ])
            ->add('consumption', TextType::class, [
                'label' => 'Zużycie',
            ])
            ->add('dateFrom', DateType::class, [
                'label' => 'Data od',
            ])
            ->add('dateTo', DateType::class, [
                'label' => 'Data do',
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
