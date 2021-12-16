<?php

namespace GCRM\CRMBundle\Service\BillingDocument\Document;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Service\BillingDocument\Base;
use GCRM\CRMBundle\Service\BillingDocument\DocumentInterface;

class Invoice extends Base implements DocumentInterface
{
    protected $entity = 'GCRM\CRMBundle\Entity\Invoice';

    public function __construct(EntityManager $em, $settings)
    {
        $this->em = $em;
        $this->settings = $settings;
    }

}