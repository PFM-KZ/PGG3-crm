<?php

namespace Wecoders\EnergyBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wecoders\EnergyBundle\Entity\PriceListDataAndTariff;

class PriceListDataAndTariffType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('tariff', EntityType::class, [
                'class' => 'WecodersEnergyBundle:Tariff',
                'label' => 'Taryfa',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => PriceListDataAndTariff::class,
        ));
    }
}
