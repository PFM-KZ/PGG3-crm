<?php

namespace Wecoders\EnergyBundle\Service\BillingDocument\Document;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Service\BillingDocument\Base;
use GCRM\CRMBundle\Service\BillingDocument\DocumentInterface;

class InvoiceCorrection extends Base implements DocumentInterface
{
    protected $entity = 'Wecoders\EnergyBundle\Entity\InvoiceCorrection';

    public function __construct(EntityManager $em, $settings)
    {
        $this->em = $em;
        $this->settings = $settings;
    }

}