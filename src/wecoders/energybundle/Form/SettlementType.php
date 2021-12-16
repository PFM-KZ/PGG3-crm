<?php

namespace Wecoders\EnergyBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettlementType extends AbstractType
{
    private $request;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $pp = $this->request->query->get('pp') ?: null;
        $dateFrom = $this->request->query->get('dateFrom') ? \DateTime::createFromFormat('d-m-Y', $this->request->query->get('dateFrom')) : null;
        $dateTo = $this->request->query->get('dateTo') ? \DateTime::createFromFormat('d-m-Y', $this->request->query->get('dateTo')) : null;

        $builder
            ->add('pp', TextType::class, [
                'label' => 'Kod PP',
                'data' => $pp,
            ])
            ->add('dateFrom', DateType::class, [
                'label' => 'Okres rozliczeniowy od (pobiera rekordy na podstawie dat odczytów bieżących)',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'datepicker'
                ],
                'data' => $dateFrom,
            ])
            ->add('dateTo', DateType::class, [
                'label' => 'Okres rozliczeniowy do (pobiera rekordy na podstawie dat odczytów bieżących)',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'datepicker'
                ],
                'data' => $dateTo,
            ])
            ->add('invoiceType', ChoiceType::class, [
                'label' => 'Typ faktury',
                'required' => true,
                'choices' => ['Rozliczeniowa' => 'InvoiceSettlementEnergy', 'Szacunkowa' => 'InvoiceEstimatedSettlementEnergy']
            ])
            ->add('omitCalculateDateFrom', CheckboxType::class, [
                'label' => 'Pomiń obliczanie daty od na podstawie ostatniego rozliczenia',
                'required' => false,
            ])
            ->add('check', SubmitType::class, [
                'label' => 'Sprawdź',
            ])
            ->add('generate', SubmitType::class, [
                'label' => 'Generuj fakturę',
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
