<?php

namespace GCRM\CRMBundle\Form;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\ClientAndUser;
use GCRM\CRMBundle\Entity\Contract;
use GCRM\CRMBundle\Entity\ContractAndContractShownDocumentTypeBeforeSign;
use GCRM\CRMBundle\Entity\ContractAndCustomerAllowedDevice;
use GCRM\CRMBundle\Entity\ContractAndService;
use GCRM\CRMBundle\Entity\ContractAttachment;
use GCRM\CRMBundle\Entity\ContractShownDocumentTypeBeforeSign;
use GCRM\CRMBundle\Entity\ContractSignPersonType;
use GCRM\CRMBundle\Entity\ContractTerminationTypeFormerServiceProvider;
use GCRM\CRMBundle\Entity\CustomerAllowedDevice;
use GCRM\CRMBundle\Entity\InvoiceType;
use GCRM\CRMBundle\Entity\Service;
use GCRM\CRMBundle\Entity\ServiceProvider;
use GCRM\CRMBundle\Entity\Tariff;
use Doctrine\ORM\EntityManagerInterface;
use GCRM\CRMBundle\Entity\User;
use GCRM\CRMBundle\Entity\UserAndCompanyWithBranch;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

class ContractType extends AbstractType
{
    /** @var  EntityManager */
    private $em;

    /** @var Contract */
    private $contract;


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var EntityManager $em */
        $em = $options['entity_manager'];
        $this->em = $em;
        /** @var Contract $contract */
        $contract = $builder->getData();
        $this->contract = $contract;

        /** @var User $user */
        $user = $options['user'];

        $isPhpanel = isset($options['is_phpanel']) ? $options['is_phpanel'] : false;

        $customerAllowedDevice = $em->getRepository('GCRMCRMBundle:CustomerAllowedDevice')->findAll();
        $savedAllowedDevices = null;
        $savedContractShownDocumentTypeBeforeSign = null;
        $savedUserAndCompanyWithBranch = null;

        if ($isPhpanel) {
            $clients = $this->getAllowedClients($contract);
            $savedAllowedDevices = $this->getSavedAllowedDevices($contract);
            $savedContractShownDocumentTypeBeforeSign = $this->getSavedShownDocuments($contract);
            $savedUserAndCompanyWithBranch = $this->getSavedUserAndCompanyWithBranch($contract);
        } else {
            $clients = $em->getRepository('GCRMCRMBundle:Client')
                ->findAll();
        }




//
//        $savedContractAttachments = $this->em->getRepository('GCRMCRMBundle:ContractAttachment')->findBy([
//            'contract' => $this->contract
//        ]);
//
//        $savedAttachments = [];
//
//        /** @var ContractAttachment $item */
//        foreach ($savedContractAttachments as $item) {
//            $savedAttachments[] = $item->getId();
//        }
//        dump($savedAttachments);






        $builder
            ->add('userAndCompanyWithBranch', ChoiceType::class, array(
                'label' => 'Firma / marka / oddział - do których klient ma zostać przypisany',
                'required' => true,
                'mapped' => false,
                'choices' => $em->getRepository('GCRMCRMBundle:UserAndCompanyWithBranch')
                    ->findBy(['user' => $user]),
                'choice_value' => function (UserAndCompanyWithBranch $entity = null) {
                    return $entity ? $entity->getId() : '';
                },
                'choice_label' => function (UserAndCompanyWithBranch $entity = null) {
                    return $entity ?: '';
                },
                'placeholder' => 'Wybierz...',
            ))
            ->add('contractNumber', TextType::class, [
                'label' => 'Numer umowy',
            ])
            ->add('telephoneNumber', TextType::class, [
                'label' => 'Numer telefonu na którym będzie świadczona usługa',
                'required' => false,
            ])
            ->add('client', ChoiceType::class, [
                'empty_data' => null,
                'label' => 'Klient',
                'choices' => $clients,
                'choice_value' => function (Client $entity = null) {
                    return $entity ? $entity->getId() : '';
                },
                'choice_label' => function (Client $entity = null) {
                    return $entity ? $entity->getPesel() : '';
                },
                'placeholder' => 'Wybierz klienta...',
            ])
            ->add('contractSignPersonType', ChoiceType::class, [
                'label' => 'Osoba podpisująca',
                'choices' => $em->getRepository('GCRMCRMBundle:ContractSignPersonType')->findAll(),
                'choice_value' => function (ContractSignPersonType $entity = null) {
                    return $entity ? $entity->getId() : '';
                },
                'choice_label' => function (ContractSignPersonType $entity = null) {
                    return $entity ? $entity->getTitle() : '';
                },
                'placeholder' => 'Wybierz wartość...',
            ])
            ->add('contractSignPersonNip', TextType::class, [
                'label' => 'NIP (w przypadku firmy)',
                'required' => false,
            ])
            ->add('contractSignPersonRegon', TextType::class, [
                'label' => 'REGON (w przypadku firmy)',
                'required' => false,
            ])
            ->add('contractSignPersonKrs', TextType::class, [
                'label' => 'KRS (w przypadku firmy)',
                'required' => false,
            ])
            ->add('contractSignPersonName', TextType::class, [
                'label' => 'Imię',
                'required' => false,
            ])
            ->add('contractSignPersonSurname', TextType::class, [
                'label' => 'Nazwisko',
                'required' => false,
            ])
            ->add('contractSignPersonPesel', TextType::class, [
                'label' => 'PESEL',
                'required' => false,
            ])
            ->add('contractSignPersonIdNumber', TextType::class, [
                'label' => 'Numer dowodu',
                'required' => false,
            ])
            ->add('signDate', DateType::class, [
                'label' => 'Data podpisania umowy',
            ])
            ->add('serviceProvider', ChoiceType::class, [
                'empty_data' => null,
                'label' => '*Dotychczasowy operator',
                'choices' => $em->getRepository('GCRMCRMBundle:ServiceProvider')
                    ->findAll(),
                'choice_value' => function (ServiceProvider $entity = null) {
                    return $entity ? $entity->getId() : '';
                },
                'choice_label' => function (ServiceProvider $entity = null) {
                    return $entity ? $entity->getTitle() : '';
                },
                'required' => true,
                'placeholder' => 'Wybierz wartość...',
            ])
            ->add('serviceProviderOther', TextType::class, [
                'label' => 'Inne (Proszę wypełnić to pole w przypadku gdy w/w opcja to inne)',
                'required' => false,
            ])
            ->add('contractTerminationTypeFormerServiceProvider', ChoiceType::class, [
                'label' => 'Typ rozwiązania umowy u poprzedniego opertora',
                'choices' => $em->getRepository('GCRMCRMBundle:ContractTerminationTypeFormerServiceProvider')->findAll(),
                'choice_value' => function (ContractTerminationTypeFormerServiceProvider $entity = null) {
                    return $entity ? $entity->getId() : '';
                },
                'choice_label' => function (ContractTerminationTypeFormerServiceProvider $entity = null) {
                    return $entity ? $entity->getTitle() : '';
                },
                'required' => true,
                'placeholder' => 'Wybierz wartość...',
            ])
            ->add('serviceProviderInternet', ChoiceType::class, [
                'empty_data' => null,
                'label' => '*Dotychczasowy operator internetowy',
                'choices' => $em->getRepository('GCRMCRMBundle:ServiceProvider')
                    ->findAll(),
                'choice_value' => function (ServiceProvider $entity = null) {
                    return $entity ? $entity->getId() : '';
                },
                'choice_label' => function (ServiceProvider $entity = null) {
                    return $entity ? $entity->getTitle() : '';
                },
                'required' => true,
                'placeholder' => 'Wybierz wartość...',
            ])
            ->add('serviceProviderOtherInternet', TextType::class, [
                'label' => 'Inne (Proszę wypełnić to pole w przypadku gdy w/w opcja to inne)',
                'required' => false,
            ])
            ->add('contractTerminationTypeFormerServiceProviderInternet', ChoiceType::class, [
                'label' => 'Typ rozwiązania umowy u poprzedniego opertora',
                'choices' => $em->getRepository('GCRMCRMBundle:ContractTerminationTypeFormerServiceProvider')->findAll(),
                'choice_value' => function (ContractTerminationTypeFormerServiceProvider $entity = null) {
                    return $entity ? $entity->getId() : '';
                },
                'choice_label' => function (ContractTerminationTypeFormerServiceProvider $entity = null) {
                    return $entity ? $entity->getTitle() : '';
                },
                'required' => true,
                'placeholder' => 'Wybierz wartość...',
            ])
            ->add('contractShownDocumentTypeBeforeSign', ChoiceType::class, [
                'label' => 'Dokumenty okazane przez klienta',
                'choices' => $em->getRepository('GCRMCRMBundle:ContractShownDocumentTypeBeforeSign')->findAll(),
                'choice_value' => function (ContractShownDocumentTypeBeforeSign $entity = null) {
                    return $entity ? $entity->getId() : '';
                },
                'choice_label' => function (ContractShownDocumentTypeBeforeSign $entity = null) {
                    return $entity ? $entity->getTitle() : '';
                },
                'expanded' => true,
                'multiple' => true,
                'mapped' => false,
            ])
            ->add('contractShownDocumentTypeBeforeSignOther', TextType::class, [
                'label' => 'Inne (Proszę wypełnić to pole w przypadku gdy w/w opcja to inne)',
                'required' => false,
            ])
            ->add('customerAllowedDevice', ChoiceType::class, [
                'label' => 'Przekazane urządzenia',
                'choices' => $customerAllowedDevice,
                'choice_value' => function (CustomerAllowedDevice $entity = null) {
                    return $entity ? $entity->getId() : '';
                },
                'choice_label' => function (CustomerAllowedDevice $entity = null) {
                    return $entity ? $entity->getTitle() : '';
                },
                'expanded' => true,
                'multiple' => true,
                'mapped' => false,
            ])
            ->add('invoiceType', ChoiceType::class, [
                'label' => 'Rodzaj faktury',
                'choices' => $em->getRepository('GCRMCRMBundle:InvoiceType')->findAll(),
                'choice_value' => function (InvoiceType $entity = null) {
                    return $entity ? $entity->getId() : '';
                },
                'choice_label' => function (InvoiceType $entity = null) {
                    return $entity ? $entity->getTitle() : '';
                },
            ])
            ->add('emailForElectronicInvoice', TextType::class, [
                'label' => 'E-mail do wysyłki faktur (jeśli wybrane)',
                'required' => false,
            ])
            ->add('contractAttachments', CollectionType::class, [
                'label' => false,
                'entry_type' => ContractAttachmentType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
            ->add('comments', TextareaType::class, [
                'label' => 'Uwagi',
                'required' => false,
            ])
            ->add('save', SubmitType::class, ['label' => 'Zapisz'])
        ;

        if ($savedAllowedDevices) {
            $builder->get('customerAllowedDevice')->setData($savedAllowedDevices);
        }
        if ($savedContractShownDocumentTypeBeforeSign) {
            $builder->get('contractShownDocumentTypeBeforeSign')->setData($savedContractShownDocumentTypeBeforeSign);
        }
        if ($savedUserAndCompanyWithBranch) {
            $builder->get('userAndCompanyWithBranch')->setData($savedUserAndCompanyWithBranch);
        }






        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $contract = $event->getData();
            $form = $event->getForm();

            $customerAllowedDevice = $this->em->getRepository('GCRMCRMBundle:CustomerAllowedDevice')->findAll();

            // check if the Product object is "new"
            // If no data is passed to the form, the data is "null".
            // This should be considered a new "Product"
            if (!$contract || null === $contract->getId()) {
                /** @var CustomerAllowedDevice $item */
                foreach ($customerAllowedDevice as $item) {
                    $form->add('customerAllowedDevice_id_' . $item->getId() . '', TextType::class, [
                        'label' => $item->getTitle() . ' (jeśli wybrane - nr IMEI / tel / inne)',
                        'required' => false,
                        'mapped' => false,
                    ]);
                }
            } else { // contract exist, so fetch the existing data
                $contractAndCustomerAllowedDevice = $this->em->getRepository('GCRMCRMBundle:ContractAndCustomerAllowedDevice')->findBy([
                    'contract' => $contract
                ]);

                /** @var CustomerAllowedDevice $item */
                foreach ($customerAllowedDevice as $item) {
                    $foundDescription = false;
                    /** @var ContractAndCustomerAllowedDevice $savedContractAndCustomerAllowedDevice */
                    foreach ($contractAndCustomerAllowedDevice as $savedContractAndCustomerAllowedDevice) {
                        if ($item->getId() == $savedContractAndCustomerAllowedDevice->getCustomerAllowedDevice()->getId()) {
                            $foundDescription = $savedContractAndCustomerAllowedDevice->getDescription();
                            break;
                        }
                    }

                    $form->add('customerAllowedDevice_id_' . $item->getId() . '', TextType::class, [
                        'label' => $item->getTitle() . ' (jeśli wybrane - nr IMEI / tel / inne)',
                        'required' => false,
                        'mapped' => false,
                        'data' => $foundDescription !== false ? $foundDescription : '',
                    ]);
                }
            }
        });

        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'onPreSetData'));
        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
    }

    private function getAllowedClients(Contract $contract)
    {
        /** @var ClientAndUser $clientAndUsers */
        $clientAndUsers = $this->em->getRepository('GCRMCRMBundle:ClientAndUser')
            ->findBy([
                'user' => $contract->getUser()
            ]);

        $clients = [];
        /** @var ClientAndUser $clientAndUser */
        foreach ($clientAndUsers as $clientAndUser) {
            $clients[] = $clientAndUser->getClient();
        }

        return count($clients) ? $clients : null;
    }

    private function getSavedAllowedDevices(Contract $contract)
    {
        $savedAllowedDevices = null;
        $savedContractAndAllowedDevices = $this->em->getRepository('GCRMCRMBundle:ContractAndCustomerAllowedDevice')->findBy(
            ['contract' => $contract]
        );

        if ($savedContractAndAllowedDevices) {
            $savedAllowedDevices = [];
            /** @var ContractAndCustomerAllowedDevice $savedContractAndAllowedDevice */
            foreach ($savedContractAndAllowedDevices as $savedContractAndAllowedDevice) {
                $savedAllowedDevices[] = $savedContractAndAllowedDevice->getCustomerAllowedDevice();
            }
        }

        return $savedAllowedDevices;
    }

    private function getSavedShownDocuments(Contract $contract)
    {
        $savedShownDocuments = null;
        $savedContractAndContractShownDocumentTypeBeforeSignAll = $this->em->getRepository('GCRMCRMBundle:ContractAndContractShownDocumentTypeBeforeSign')->findBy(
            ['contract' => $contract]
        );

        if ($savedContractAndContractShownDocumentTypeBeforeSignAll) {
            $savedShownDocuments = [];
            /** @var ContractAndContractShownDocumentTypeBeforeSign $item */
            foreach ($savedContractAndContractShownDocumentTypeBeforeSignAll as $item) {
                $savedShownDocuments[] = $item->getContractShownDocumentTypeBeforeSign();
            }
        }

        return $savedShownDocuments;
    }

    private function getSavedServices(Contract $contract)
    {
        $savedServices = null;
        $savedContractAndService = $this->em->getRepository('GCRMCRMBundle:ContractAndService')->findBy(
            ['contract' => $this->contract]
        );

        if ($savedContractAndService) {
            $savedServices = [];
            /** @var ContractAndService $item */
            foreach ($savedContractAndService as $item) {
                $savedServices[] = $item->getService();
            }
        }

        return $savedServices;
    }

    private function getSavedUserAndCompanyWithBranch(Contract $contract)
    {
        return $this->em->getRepository('GCRMCRMBundle:UserAndCompanyWithBranch')
            ->findOneBy([
                'user' => $contract->getUser(),
                'company' => $contract->getCompany(),
                'brand' => $contract->getBrand(),
                'branch' => $contract->getBranch()
            ]);
    }

    protected function addElements(FormInterface $form, Tariff $tariff = null) {
        $form->add('tariff', ChoiceType::class, array(
            'label' => 'Cennik',
            'choices' => $this->em->getRepository('GCRMCRMBundle:Tariff')
                ->findBy([
                    'isActive' => true
                ]),
            'choice_value' => function (Tariff $entity = null) {
                return $entity ? $entity->getId() : '';
            },
            'choice_label' => function (Tariff $entity = null) {
                return $entity ? $entity->getTitle() : '';
            },
            'required' => true,
            'data' => $tariff,
            'placeholder' => 'Wybierz wartość...',
        ));



//        $services = array();
//
//        if ($tariff) {
//            $repoService = $this->em->getRepository('GCRMCRMBundle:TariffAndService');
//
//            $services = $repoService->createQueryBuilder("q")
//                ->where("q.tariff = :tariffId")
//                ->setParameter("tariffId", $tariff->getId())
//                ->getQuery()
//                ->getResult();
//        } else {
//            $services = null;
//        }

        $savedServices = $this->getSavedServices($this->contract);

        $form->add('services', EntityType::class, array(
            'label' => 'Usługi',
            'expanded' => true,
            'multiple' => true,
            'mapped' => false,
            'placeholder' => 'Wybierz najpierw cennik...',
            'class' => 'GCRMCRMBundle:Service',
            'data' => $savedServices
        ));
    }

    function onPreSubmit(FormEvent $event) {
        $form = $event->getForm();
        $data = $event->getData();

        // Search for selected City and convert it into an Entity
        $tariff = $this->em->getRepository('GCRMCRMBundle:tariff')->find($data['tariff']);

        $this->addElements($form, $tariff);
    }

    function onPreSetData(FormEvent $event) {
        $contract = $event->getData();
        $form = $event->getForm();

        // When you create a new person, the City is always empty
        $tariff = $contract->getTariff() ? $contract->getTariff() : null;

        $this->addElements($form, $tariff);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Contract::class,
        ));

        $resolver->setRequired(['entity_manager', 'is_phpanel', 'user']);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'gcrmcrmbundle_contract';
    }

}
