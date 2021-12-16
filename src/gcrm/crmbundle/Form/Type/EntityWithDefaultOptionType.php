<?php

namespace GCRM\CRMBundle\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class EntityWithDefaultOptionType extends AbstractType
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $data = $this->em->getRepository($options['class'])->find($options['data']);
        $builder->setData($data);

        parent::buildForm($builder, $options);
    }

    public function getParent()
    {
        return EntityType::class;
    }
}
