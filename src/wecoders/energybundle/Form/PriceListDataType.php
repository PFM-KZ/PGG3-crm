<?php

namespace Wecoders\EnergyBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wecoders\EnergyBundle\Entity\PriceListData;
use Wecoders\EnergyBundle\Service\TariffModel;

class PriceListDataType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('tariffTypeCode', ChoiceType::class, [
                'label' => 'Strefa',
                'placeholder' => 'Wybierz...',
                'choices' => array_flip(TariffModel::getOptionArray()),
            ])
            ->add('priceListDataAndTariffs', CollectionType::class, [
                'label' => 'Taryfy',
                'entry_type' => 'Wecoders\EnergyBundle\Form\PriceListDataAndTariffType',
                'by_reference' => false,
                'allow_add' => true,
                'allow_delete' => true
            ])
            ->add('priceListDataAndYearWithPrices', CollectionType::class, [
                'label' => 'Lata i ceny',
                'entry_type' => 'Wecoders\EnergyBundle\Form\PriceListDataAndYearWithPriceType',
                'by_reference' => false,
                'allow_add' => true,
                'allow_delete' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => PriceListData::class,
        ));
    }
}
