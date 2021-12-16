<?php

namespace GCRM\CRMBundle\Form;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Contract;
use GCRM\CRMBundle\Entity\ContractAndService;
use GCRM\CRMBundle\Entity\Service;
use GCRM\CRMBundle\Entity\Tariff;
use GCRM\CRMBundle\Entity\TariffAndService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContractAndServiceType extends AbstractType
{
    /** @var  EntityManager */
    private $em;

    private $requestStack;

    public function __construct(EntityManager $em, RequestStack $requestStack)
    {
        $this->em = $em;
        $this->requestStack = $requestStack;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $request = $this->requestStack->getCurrentRequest();
        $contractId = $request->query->has('id') ? $request->query->get('id') : false;

        $services = [];
        if ($contractId) {
            /** @var Contract $contract */
            $contract = $this->em->getRepository('GCRMCRMBundle:Contract')->find($contractId);
            $tariff = $contract->getTariff();


            $tariffAndServices = $this->em->getRepository('GCRMCRMBundle:TariffAndService')
                ->findBy(['tariff' => $tariff]);

            if ($tariffAndServices) {
                /** @var TariffAndService $tariffAndService */
                foreach ($tariffAndServices as $tariffAndService) {
                    $services[] = $tariffAndService->getService();
                }
            }
        }

        $builder
            ->add('service', ChoiceType::class, [
                'label' => 'Usługa',
//                'choices' => $services,
                'choices' => $this->em->getRepository('GCRMCRMBundle:Service')->findAll(),
                'choice_value' => function (Service $entity = null) {
                    return $entity ? $entity->getId() : '';
                },
                'choice_label' => function (Service $entity = null) {
                    return $entity ? $entity->getTitle() : '';
                },
            ])
            ->add('durationInMonths', TextType::class, [
                'label' => 'Okres (miesiące)',
            ])
            ->add('activationDate', DateType::class, [
                'label' => 'Data aktywacji',
            ])
            ->add('isPartialBilling', null, [
                'label' => 'Rozliczanie częściowe',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => ContractAndService::class,
        ));
    }
}
