<?php

namespace Wecoders\EnergyBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wecoders\EnergyBundle\Entity\PriceListDataAndTariffWithTariffType;

class PriceListDataAndTariffWithTariffTypeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('tariffAndTariffType', EntityType::class, [
                'class' => 'WecodersEnergyBundle:TariffAndTariffType',
                'label' => 'Taryfa i strefa',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => PriceListDataAndTariffWithTariffType::class,
        ));
    }
}
