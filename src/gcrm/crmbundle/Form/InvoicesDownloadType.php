<?php

namespace GCRM\CRMBundle\Form;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvoicesDownloadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $lsCreatedDateFrom = null;
        if (isset($options['data']['createdDateFrom'])) {
            $tempDate = $options['data']['createdDateFrom'];
            if ($tempDate['year'] && $tempDate['month'] && $tempDate['day']) {
                $dateTime = new \DateTime();
                $dateTime->setDate($tempDate['year'], $tempDate['month'], $tempDate['day']);
                $dateTime->setTime(0, 0, 0);
                $lsCreatedDateFrom = $dateTime;
            }
        }

        $lsCreatedDateTo = null;
        if (isset($options['data']['createdDateFrom'])) {
            $tempDate = $options['data']['createdDateFrom'];
            if ($tempDate['year'] && $tempDate['month'] && $tempDate['day']) {
                $dateTime = new \DateTime();
                $dateTime->setDate($tempDate['year'], $tempDate['month'], $tempDate['day']);
                $dateTime->setTime(0, 0, 0);
                $lsCreatedDateTo = $dateTime;
            }
        }

        $builder
            ->add('createdDateFrom', DateType::class, [
                'label' => 'Data wystawienia od',
                'data' => $lsCreatedDateFrom,
                'required' => false,
            ])
            ->add('createdDateTo', DateType::class, [
                'label' => 'do',
                'data' => $lsCreatedDateTo,
                'required' => false,
            ])
            ->add('download', SubmitType::class, ['label' => 'Pobierz'])
        ;
    }

    public function getBlockPrefix()
    {
        return '';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null,
        ));
    }
}
