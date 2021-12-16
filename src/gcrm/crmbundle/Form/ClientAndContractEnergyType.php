<?php

namespace GCRM\CRMBundle\Form;

use GCRM\CRMBundle\Entity\ClientAndContractEnergy;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientAndContractEnergyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('contract', EntityType::class, [
                'class' => 'GCRMCRMBundle:ContractEnergy',
                'label' => 'Umowa Prąd',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ClientAndContractEnergy::class,
        ));
    }
}
