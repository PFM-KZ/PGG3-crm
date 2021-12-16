<?php

namespace GCRM\CRMBundle\Form;

use GCRM\CRMBundle\Form\Type\LazyChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TZiebura\CorrespondenceBundle\Entity\ThreadAndClient;

class ThreadClientType extends AbstractType
{    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('client', LazyChoiceType::class, [
            'data_class' => 'GCRM\CRMBundle\Entity\Client',
            'label' => false,
            'attr' => [
                'placedholder' => 'Nie dotyczy klienta',
                'data-ajax-lazyload' => 'true',
                'data-ajax-route' => 'fetchClientData',
            ],
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ThreadAndClient::class,
        ));
    }
}