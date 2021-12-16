<?php

namespace Wecoders\EnergyBundle\Form\Statistics;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActiveClientsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('downloadXlsx', SubmitType::class, [
                'label' => 'Pobierz do xlsx',
                'attr' => ['class' => 'btn btn-primary']
            ])
        ;

        if (isset($options['data']['addButtons']) && is_array($options['data']['addButtons'])) {
            foreach ($options['data']['addButtons'] as $buttonData) {
                $builder
                    ->add($buttonData['name'], SubmitType::class, [
                        'label' => $buttonData['label'],
                        'attr' => ['class' => 'btn btn-primary']
                    ])
                ;
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null,
        ));
    }
}
