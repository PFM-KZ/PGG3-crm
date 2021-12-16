<?php

namespace Wecoders\EnergyBundle\Form;

use Doctrine\DBAL\Types\DecimalType;
use GCRM\CRMBundle\Service\GTU;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvoiceServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', IntegerType::class, [
                'label' => 'ID',
                'disabled' => true
            ])
            ->add('title', TextType::class, [
                'label' => 'Nazwa',
            ])
            ->add('priceValue', TextType::class, [
                'label' => 'Cena netto',
            ])
            ->add('quantity', TextType::class, [
                'label' => 'Ilość',
            ])
            ->add('unit', TextType::class, [
                'label' => 'Jednostka miary',
            ])
            ->add('zone', TextType::class, [
                'label' => 'Strefa',
            ])
            ->add('deviceNumber', TextType::class, [
                'label' => 'Numer licznika',
            ])
            ->add('excise', NumberType::class, [
                'label' => 'Akcyza',
            ])
            ->add('netValue', NumberType::class, [
                'label' => 'Wartość netto',
            ])
            ->add('vatPercentage', TextType::class, [
                'label' => 'Vat %',
            ])
            ->add('grossValue', NumberType::class, [
                'label' => 'Wartość brutto',
                'disabled' => true,
            ])
            ->add('gtu', ChoiceType::class, [
                'label' => 'GTU',
                'choices' => array_flip(GTU::getOptionArray()),
                'placeholder' => 'Wybierz opcję...',
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
