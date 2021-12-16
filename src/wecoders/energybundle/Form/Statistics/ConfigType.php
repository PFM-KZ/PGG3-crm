<?php

namespace Wecoders\EnergyBundle\Form\Statistics;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigType extends AbstractType
{
    const GROUP_BY_TYPE_DAY = 1;
    const GROUP_BY_TYPE_MONTH = 2;

    private $request;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    public static function getGroupByTypeDefaultOption()
    {
        return self::GROUP_BY_TYPE_MONTH;
    }

    public static function getGroupByTypeOptionsArray()
    {
        return [
            self::GROUP_BY_TYPE_MONTH => 'Miesiąc',
            self::GROUP_BY_TYPE_DAY => 'Dzień',
        ];
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $request = $this->request;

        $lsDateFrom = $request->query->has('lsDateFrom') && $request->query->get('lsDateFrom') ? \DateTime::createFromFormat('Y-m-d', $request->query->get('lsDateFrom')): null;
        $lsDateTo = $request->query->has('lsDateTo') && $request->query->get('lsDateTo') ? \DateTime::createFromFormat('Y-m-d', $request->query->get('lsDateTo')): null;

        $builder
            ->add('lsDateFrom', DateType::class, [
                'label' => 'Data od',
                'data' => $lsDateFrom,
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'datepicker'
                ]
            ])
            ->add('lsDateTo', DateType::class, [
                'label' => 'Data do',
                'data' => $lsDateTo,
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'datepicker'
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Pokaż',
            ])
        ;

        if (isset($options['data']['addGroupByType']) && $options['data']['addGroupByType']) {
            $groupByType = $request->query->has('lsGroupByType') && $request->query->get('lsGroupByType') ? (int) $request->query->get('lsGroupByType') : null;

            if (!$groupByType) {
                $groupByType = self::getGroupByTypeDefaultOption();
            }

            $builder->add('lsGroupByType', ChoiceType::class, [
                'label' => 'Grupowanie po',
                'required' => true,
                'expanded' => false,
                'multiple' => false,
                'choices' => array_flip(self::getGroupByTypeOptionsArray()),
                'data' => $groupByType
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null,
        ));
    }

    public function getBlockPrefix()
    {
        return '';
    }
}
