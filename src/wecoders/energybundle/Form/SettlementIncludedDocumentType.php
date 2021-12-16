<?php

namespace Wecoders\EnergyBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wecoders\EnergyBundle\Model\SettlementIncludedDocument;

class SettlementIncludedDocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('documentNumber', TextType::class, [
                'label' => 'Numer faktury',
            ])
            ->add('netValue', NumberType::class, [
                'label' => 'Wartość netto',
            ])
            ->add('vatValue', NumberType::class, [
                'label' => 'Wartość VAT',
            ])
            ->add('grossValue', NumberType::class, [
                'label' => 'Wartość brutto',
            ])
            ->add('exciseValue', NumberType::class, [
                'label' => 'Wartość akcyzy',
            ])
            ->add('billingPeriodFrom', DateType::class, [
                'label' => 'Okres rozliczeniowy od',
            ])
            ->add('billingPeriodTo', DateType::class, [
                'label' => 'Okres rozliczeniowy do',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => SettlementIncludedDocument::class,
        ));
    }
}
