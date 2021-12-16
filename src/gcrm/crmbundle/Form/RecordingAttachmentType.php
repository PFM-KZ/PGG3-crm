<?php

namespace GCRM\CRMBundle\Form;

use GCRM\CRMBundle\Entity\ContractAndCustomerAllowedDevice;
use GCRM\CRMBundle\Entity\ContractAttachment;
use GCRM\CRMBundle\Entity\RecordingAttachment;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

class RecordingAttachmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('urlFile', VichFileType::class, [
                'label' => 'Nagranie',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => RecordingAttachment::class,
        ));
    }
}
