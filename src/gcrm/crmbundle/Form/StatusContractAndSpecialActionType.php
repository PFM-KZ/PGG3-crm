<?php

namespace GCRM\CRMBundle\Form;

use GCRM\CRMBundle\Entity\StatusContractAndSpecialAction;
use GCRM\CRMBundle\Service\StatusContractModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StatusContractAndSpecialActionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = StatusContractModel::getSpecialActionOptionArray();
        $builder
            ->add('option', ChoiceType::class, [
                'label' => 'Akcja',
                'choices' => array_flip($choices),
                'placeholder' => 'Wybierz wartość...',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => StatusContractAndSpecialAction::class,
        ));
    }
}
