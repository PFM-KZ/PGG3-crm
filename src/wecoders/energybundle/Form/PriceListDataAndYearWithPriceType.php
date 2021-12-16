<?php

namespace Wecoders\EnergyBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wecoders\EnergyBundle\Entity\PriceListDataAndYearWithPrice;

class PriceListDataAndYearWithPriceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('year', ChoiceType::class, [
                'label' => 'Rok',
                'placeholder' => 'Wybierz...',
                'choices' => [
                    2013 => 2013,
                    2014 => 2014,
                    2015 => 2015,
                    2016 => 2016,
                    2017 => 2017,
                    2018 => 2018,
                    2019 => 2019,
                    2020 => 2020,
                    2021 => 2021,
                    2022 => 2022,
                    2023 => 2023,
                    2024 => 2024,
                    2025 => 2025,
                    2026 => 2026,
                    2027 => 2027,
                    2028 => 2028,
                    2029 => 2029,
                    2030 => 2030,
                ],
                'required' => true,
            ])
            ->add('netValue', TextType::class, [
                'label' => 'Netto',
                'required' => true,
            ])
            ->add('grossValue', TextType::class, [
                'label' => 'Brutto',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => PriceListDataAndYearWithPrice::class,
        ));
    }
}
