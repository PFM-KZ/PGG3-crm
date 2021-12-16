<?php

namespace Wecoders\EnergyBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wecoders\EnergyBundle\Entity\OsdAndOsdDataWithData;

class OsdAndOsdDataWithDataType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('tariff', TextType::class, [
                'label' => 'Taryfa',
            ])
            ->add('feeConstant', TextType::class, [
                'label' => 'Opłata stała',
            ])
            ->add('feeVariable', TextType::class, [
                'label' => 'Opłata zmienna',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => OsdAndOsdDataWithData::class,
        ));
    }
}
