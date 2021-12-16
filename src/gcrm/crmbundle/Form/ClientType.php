<?php

namespace GCRM\CRMBundle\Form;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\User;
use GCRM\CRMBundle\Entity\UserAndCompanyWithBranch;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientType extends AbstractType
{
    /** @var EntityManager */
    private $em;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var EntityManager $em */
        $em = $options['entity_manager'];
        $this->em = $em;

        /** @var Client $contract */
        $client = $builder->getData();

        /** @var User $user */
        $user = $options['user'];

        $isPhpanel = isset($options['is_phpanel']) ? $options['is_phpanel'] : false;

        $savedUserAndCompanyWithBranch = null;

        if ($isPhpanel) {
            $savedUserAndCompanyWithBranch = $this->getSavedUserAndCompanyWithBranch($client);
        }

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
            ->add('name', TextType::class, [
                'label' => 'Imię',
            ])
            ->add('surname', TextType::class, [
                'label' => 'Nazwisko',
            ])
            ->add('telephoneNr', TextType::class, [
                'label' => 'Nr telefonu',
            ])
            ->add('pesel', TextType::class, [
                'label' => 'PESEL',
            ])
            ->add('nip', TextType::class, [
                'label' => 'NIP',
                'required' => false,
            ])
            ->add('regon', TextType::class, [
                'label' => 'REGON',
                'required' => false,
            ])
            ->add('email', TextType::class, [
                'label' => 'E-mail',
                'required' => false,
            ])
            ->add('idNr', TextType::class, [
                'label' => 'Nr dowodu osobistego',
                'required' => false,
            ])
            ->add('city', TextType::class, [
                'label' => 'Miasto',
            ])
            ->add('street', TextType::class, [
                'label' => 'Ulica',
            ])
            ->add('houseNr', TextType::class, [
                'label' => 'Nr domu',
            ])
            ->add('apartmentNr', TextType::class, [
                'label' => 'Nr lokalu',
            ])
            ->add('zipCode', TextType::class, [
                'label' => 'Kod pocztowy',
            ])
            ->add('postOffice', TextType::class, [
                'label' => 'Poczta',
            ])
            ->add('county', TextType::class, [
                'label' => 'Powiat',
            ])
            ->add('contactTelephoneNr', TextType::class, [
                'label' => 'Nr telefonu kontaktowego',
            ])
            ->add('correspondenceCity', TextType::class, [
                'label' => 'Miasto',
            ])
            ->add('correspondenceStreet', TextType::class, [
                'label' => 'Ulica',
            ])
            ->add('correspondenceHouseNr', TextType::class, [
                'label' => 'Nr domu',
            ])
            ->add('correspondenceApartmentNr', TextType::class, [
                'label' => 'Nr lokalu',
            ])
            ->add('correspondenceZipCode', TextType::class, [
                'label' => 'Kod pocztowy',
            ])
            ->add('correspondencePostOffice', TextType::class, [
                'label' => 'Poczta',
            ])
            ->add('correspondenceCounty', TextType::class, [
                'label' => 'Powiat',
            ])
            ->add('comments', TextareaType::class, [
                'label' => 'Uwagi',
                'required' => false,
            ])
            ->add('save', SubmitType::class, ['label' => 'Zapisz'])
        ;

        if ($savedUserAndCompanyWithBranch) {
            $builder->get('userAndCompanyWithBranch')->setData($savedUserAndCompanyWithBranch);
        }
    }

    private function getSavedUserAndCompanyWithBranch(Client $client)
    {
        return $this->em->getRepository('GCRMCRMBundle:UserAndCompanyWithBranch')
            ->findOneBy([
                'user' => $client->getUser(),
                'company' => $client->getCompany(),
                'brand' => $client->getBrand(),
                'branch' => $client->getBranch()
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Client::class,
        ));

        $resolver->setRequired(['entity_manager', 'is_phpanel', 'user']);
    }
}
