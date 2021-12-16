<?php

namespace GCRM\CRMBundle\Service\BillingDocument;

use Doctrine\ORM\EntityManager;

abstract class Base
{
    /** @var EntityManager */
    protected $em;

    protected $entity;

    protected $orderBy;

    protected $orderPosition = 'ASC';

    protected $settings;

    public function getDocumentRowsByClientId($client)
    {
        return $this->em->getRepository($this->entity)->findBy([
            'client' => $client
        ], [$this->settings['orderBy'] => $this->settings['orderPosition']]);
    }

    public function getEntity()
    {
        return $this->entity;
    }

}