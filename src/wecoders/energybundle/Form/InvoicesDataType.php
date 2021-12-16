<?php

namespace Wecoders\EnergyBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvoicesDataType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', IntegerType::class, [
                'label' => 'ID',
                'disabled' => true
            ])
            ->add('ppName', TextType::class, [
                'label' => 'PP nazwa',
            ])
            ->add('ppZipCode', TextType::class, [
                'label' => 'PP kod pocztowy',
            ])
            ->add('ppCity', TextType::class, [
                'label' => 'PP miasto',
            ])
            ->add('ppStreet', TextType::class, [
                'label' => 'PP ulica',
            ])
            ->add('ppHouseNr', TextType::class, [
                'label' => 'PP nr domu',
            ])
            ->add('ppApartmentNr', TextType::class, [
                'label' => 'PP nr lokalu',
            ])
            ->add('ppEnergy', TextType::class, [
                'label' => 'PPE/PPG',
            ])
            ->add('tariff', TextType::class, [
                'label' => 'Taryfa',
            ])
            ->add('sellerTariff', TextType::class, [
                'label' => 'Taryfa sprzedawcy',
            ])
            ->add('distributionTariff', TextType::class, [
                'label' => 'Taryfa dystrybucji',
            ])
            ->add('services', CollectionType::class, [
                'label' => 'Produkty',
                'entry_type' => 'Wecoders\EnergyBundle\Form\InvoiceServiceType',
                'by_reference' => false,
                'allow_add' => true,
                'allow_delete' => true
            ])
            ->add('consumptionByDevices', CollectionType::class, [
                'label' => 'Podział zużycia na liczniki',
                'entry_type' => 'Wecoders\EnergyBundle\Form\InvoiceConsumptionByDeviceDataType',
                'by_reference' => false,
                'allow_add' => true,
                'allow_delete' => true
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
