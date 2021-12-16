<?php

namespace Wecoders\EnergyBundle\Form;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wecoders\EnergyBundle\Entity\PriceListAndServiceData;
use Wecoders\EnergyBundle\Entity\Service;

class PriceListAndServiceDataType extends AbstractType
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('service', ChoiceType::class, array(
                'label' => 'Usługa',
                'choices' => $this->em->getRepository('WecodersEnergyBundle:Service')->findAll(),
                'choice_value' => function (Service $entity = null) {
                    return $entity ? $entity->getId() : '';
                },
                'choice_label' => function (Service $entity = null) {
                    return $entity ? $entity->getTitle() : '';
                },
                'placeholder' => 'Wybierz...',
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => PriceListAndServiceData::class,
        ));
    }
}
