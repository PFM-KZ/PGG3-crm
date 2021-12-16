<?php

namespace Wecoders\EnergyBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvoiceCollectiveType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', IntegerType::class, [
                'label' => 'ID',
                'disabled' => true
            ])
            ->add('number', TextType::class, [
                'label' => 'Numer faktury',
            ])
            ->add('pp', TextType::class, [
                'label' => 'PP',
            ])
            ->add('netValue', NumberType::class, [
                'label' => 'Wartość netto',
            ])
            ->add('vatPercentage', TextType::class, [
                'label' => 'Vat %',
            ])
            ->add('vatValue', TextType::class, [
                'label' => 'Podatek VAT',
            ])
            ->add('grossValue', NumberType::class, [
                'label' => 'Wartość brutto',
            ])
            ->add('consumption', TextType::class, [
                'label' => 'Zużycie',
            ])
            ->add('exciseValue', TextType::class, [
                'label' => 'Akcyza kwota łączna',
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
