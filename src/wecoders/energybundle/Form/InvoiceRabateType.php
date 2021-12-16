<?php

namespace Wecoders\EnergyBundle\Form;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\User;
use GCRM\CRMBundle\Entity\UserAndCompanyWithBranch;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvoiceRabateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', IntegerType::class, [
                'label' => 'ID',
                'disabled' => true
            ])
            ->add('title', ChoiceType::class, [
                'label' => 'Nazwa rabatu',
                'choices' => [
                    'Bonifikata' => 'Bonifikata'
                ]
            ])
            ->add('netValue', TextType::class, [
                'label' => 'Wartość netto',
            ])
            ->add('vatPercentage', TextType::class, [
                'label' => 'Vat %',
            ])
            ->add('grossValue', TextType::class, [
                'label' => 'Wartość brutto',
                'disabled' => true,
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
