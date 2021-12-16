<?php

namespace GCRM\CRMBundle\Form;

use GCRM\CRMBundle\Entity\CampaignAndCampaignFieldType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CampaignAndCampaignFieldTypeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'Etykieta'
            ])
            ->add('campaignFieldType', EntityType::class, [
                'class' => 'GCRMCRMBundle:CampaignFieldType',
                'label' => 'Typ pola',
                'mapped' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => CampaignAndCampaignFieldType::class,
        ));
    }
}
