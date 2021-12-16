<?php

namespace GCRM\CRMBundle\Form;

use GCRM\CRMBundle\Entity\ContractAndCustomerAllowedDevice;
use GCRM\CRMBundle\Entity\ContractAttachment;
use GCRM\CRMBundle\Entity\ContractGasAttachment;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

class ContractGasAttachmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('urlFile', VichFileType::class, [
                'label' => 'Plik',
            ])
            ->add('registerNumber', TextType::class, [
                'label' => 'Nr ewidencyjny',
                'required' => false,
            ])
            ->add('boxNumber', TextType::class, [
                'label' => 'Nr kartonu',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ContractGasAttachment::class,
        ));
    }
}
