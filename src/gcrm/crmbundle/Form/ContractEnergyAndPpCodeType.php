<?php

namespace GCRM\CRMBundle\Form;

use GCRM\CRMBundle\Entity\ContractEnergyAndPpCode;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContractEnergyAndPpCodeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('ppCode', TextType::class, [
                'label' => 'Kod PP',
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
        $resolver->setDefaults([]);
    }
}
