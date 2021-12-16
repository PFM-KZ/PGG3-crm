<?php

namespace Wecoders\EnergyBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wecoders\EnergyBundle\Entity\OsdAndOsdData;

class OsdAndOsdDataType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Nazwa',
            ])
            ->add('activeFrom', DateType::class, [
                'label' => 'Obowiązuje od',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'datepicker'
                ],
            ])
            ->add('OsdAndOsdDataWithDatas', CollectionType::class, [
                'label' => 'Data',
                'entry_type' => 'Wecoders\EnergyBundle\Form\OsdAndOsdDataWithDataType',
                'by_reference' => false,
                'allow_add' => true,
                'allow_delete' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => OsdAndOsdData::class,
        ));
    }
}
