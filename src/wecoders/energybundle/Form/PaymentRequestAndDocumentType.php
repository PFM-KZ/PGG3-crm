<?php

namespace Wecoders\EnergyBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wecoders\EnergyBundle\Entity\PaymentRequestAndDocument;

class PaymentRequestAndDocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('billingPeriod', TextType::class, [
                'label' => 'Data sprzedaży',
                'required' => false,
            ])
            ->add('billingPeriodFrom', DateType::class, [
                'label' => 'Okres rozliczeniowy od',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'datepicker'
                ],
            ])
            ->add('billingPeriodTo', DateType::class, [
                'label' => 'Okres rozliczeniowy do',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'datepicker'
                ],
            ])
            ->add('daysOverdue', TextType::class, [
                'label' => 'Dni po terminie płatności',
                'required' => true,
            ])
            ->add('documentNumber', TextType::class, [
                'label' => 'Numer dokumentu',
                'required' => true,
            ])
            ->add('toPay', TextType::class, [
                'label' => 'Do zapłaty',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => PaymentRequestAndDocument::class,
        ));
    }
}
