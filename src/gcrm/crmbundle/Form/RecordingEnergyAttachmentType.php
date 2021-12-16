<?php

namespace GCRM\CRMBundle\Form;

use GCRM\CRMBundle\Entity\RecordingEnergyAttachment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

class RecordingEnergyAttachmentType extends AbstractType
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
            'data_class' => RecordingEnergyAttachment::class,
        ));
    }
}
