<?php

namespace GCRM\CRMBundle\Form;

use GCRM\CRMBundle\Entity\ContractEnergyAndPriceList;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContractEnergyAndPriceListType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('priceList', EntityType::class, [
                'class' => 'WecodersEnergyBundle:PriceList',
                'label' => 'Cennik',
                'placeholder' => 'Wybierz cennik...',
            ])
            ->add('fromDate', DateType::class, [
                'label' => 'Ważny od',
                'widget' => 'single_text',
                'attr' => ['class' => 'datepicker']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ContractEnergyAndPriceList::class,
        ));
    }
}
