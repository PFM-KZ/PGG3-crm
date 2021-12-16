<?php

namespace GCRM\CRMBundle\Form;

use Doctrine\DBAL\Types\BooleanType;
use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\Invoice;
use GCRM\CRMBundle\Entity\User;
use GCRM\CRMBundle\Service\ListSearcher\EntityListSearcherInterface;
use GCRM\CRMBundle\Service\ListSearcherStrategyInitializer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ListSearchType extends AbstractType
{
    /** @var  EntityManager */
    private $em;

    private $container;

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // INIT VALUES
        $builder
            ->add('entity', HiddenType::class)
            ->add('action', HiddenType::class)
            ->add('menuIndex', HiddenType::class)
            ->add('submenuIndex', HiddenType::class)
            ->add('statusDepartment', HiddenType::class)
            ->add('sortField', HiddenType::class)
            ->add('sortDirection', HiddenType::class)
        ;

        /** @var ListSearcherStrategyInitializer $listStrategyInitializer */
        $listStrategyInitializer = $this->container->get('gcrm\crmbundle\service\listsearcherstrategyinitializer');
        /** @var EntityListSearcherInterface $chosenObject */
        $chosenObject = $listStrategyInitializer->chooseObjectByEntity($options['data']['entityClass']);
        if ($chosenObject) {
            $chosenObject->addFields($builder, $options, $this->em);
        }

        $builder->add('listSearch', SubmitType::class, ['label' => 'Szukaj', 'attr' => ['class' => 'btn btn-success']]);
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
