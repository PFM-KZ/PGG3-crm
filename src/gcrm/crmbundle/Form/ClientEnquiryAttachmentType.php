<?php

namespace GCRM\CRMBundle\Form;

use GCRM\CRMBundle\Entity\ClientEnquiry;
use GCRM\CRMBundle\Entity\ClientEnquiryAttachment;
use GCRM\CRMBundle\Entity\ContractEnergyAttachment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ClientEnquiryAttachmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('urlFile', VichFileType::class, [
                'label' => 'Plik',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ClientEnquiryAttachment::class,
        ));
    }
}
