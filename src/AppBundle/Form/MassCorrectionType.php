<?php

namespace AppBundle\Form;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wecoders\EnergyBundle\Entity\Tariff;

class MassCorrectionType extends AbstractType
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
//            ->add('sellerTariff', ChoiceType::class, array(
//                'label' => 'Taryfa sprzedawcy',
//                'choices' => $this->em->getRepository('WecodersEnergyBundle:Tariff')->findAll(),
//                'choice_value' => function (Tariff $entity = null) {
//                    return $entity ? $entity->getId() : '';
//                },
//                'choice_label' => function (Tariff $entity = null) {
//                    return $entity ?: '';
//                },
//                'placeholder' => 'Wybierz...',
//            ))
//            ->add('distributionTariff', ChoiceType::class, array(
//                'label' => 'Taryfa dystrybucyjna',
//                'choices' => $this->em->getRepository('WecodersEnergyBundle:Tariff')->findAll(),
//                'choice_value' => function (Tariff $entity = null) {
//                    return $entity ? $entity->getId() : '';
//                },
//                'choice_label' => function (Tariff $entity = null) {
//                    return $entity ?: '';
//                },
//                'placeholder' => 'Wybierz...',
//            ))
            ->add('file', FileType::class, [
                'label' => 'Plik',
                'multiple' => false
            ])

            ->add('save', SubmitType::class, ['label' => 'Utwórz'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null,
        ));
    }
}
