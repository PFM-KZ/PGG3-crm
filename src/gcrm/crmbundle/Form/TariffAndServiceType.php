<?php

namespace GCRM\CRMBundle\Form;

use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\ContractAndService;
use GCRM\CRMBundle\Entity\TariffAndService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TariffAndServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('service', EntityType::class, [
                'class' => 'GCRMCRMBundle:Service',
                'label' => 'Usługa',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => TariffAndService::class,
        ));
    }
}
