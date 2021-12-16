<?php

namespace GCRM\CRMBundle\Form;

use GCRM\CRMBundle\Service\PaymentImporterModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImporterPaymentsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $bankTypes = PaymentImporterModel::getOptionArray();

        $builder
            ->add('file', FileType::class, [
                'label' => 'Plik',
            ])
            ->add('bank', ChoiceType::class, [
                'label' => 'Bank',
                'choices' => array_flip($bankTypes),
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Zapisz',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null,
        ));
    }
}
