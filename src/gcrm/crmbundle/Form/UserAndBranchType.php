<?php

namespace GCRM\CRMBundle\Form;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\UserAndBranch;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserAndBranchType extends AbstractType
{
    /** @var  EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('branch', EntityType::class, [
                'class' => 'GCRMCRMBundle:Branch',
                'label' => 'Oddział',
                'empty_data' => null,
                'placeholder' => 'Wybierz oddział...',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => UserAndBranch::class,
        ));
    }
}
