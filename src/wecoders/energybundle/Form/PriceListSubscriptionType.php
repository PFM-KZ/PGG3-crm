<?php

namespace Wecoders\EnergyBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wecoders\EnergyBundle\Entity\PriceListSubscription;

class PriceListSubscriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('tariff', EntityType::class, [
                'class' => 'WecodersEnergyBundle:Tariff',
                'label' => 'Taryfa',
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
            'data_class' => PriceListSubscription::class,
        ));
    }
}
