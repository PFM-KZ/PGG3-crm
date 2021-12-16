<?php

namespace GCRM\CRMBundle\Form;

use GCRM\CRMBundle\Service\PaymentImporterModel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImporterPaymentsOldType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $bankTypes = PaymentImporterModel::getOptionArray();

        $builder
            ->add('files', FileType::class, [
                'label' => 'Plik',
                'multiple' => true,
            ])
            ->add('bank', ChoiceType::class, [
                'label' => 'Bank',
                'choices' => array_flip([PaymentImporterModel::BANK_TYPE_PEKAO => PaymentImporterModel::getOptionByValue(PaymentImporterModel::BANK_TYPE_PEKAO)]),
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
