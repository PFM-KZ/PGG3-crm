<?php

namespace Wecoders\EnergyBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Wecoders\EnergyBundle\Entity\IsDocumentReadyForBankAccountChangeInterface;

class BillingRecordGeneratedEvent extends Event
{
    protected $billingRecord;

    public function __construct($billingRecord)
    {
        $this->billingRecord = $billingRecord;
    }

    /**
     * @return null
     */
    public function getBillingRecord()
    {
        return $this->billingRecord;
    }

    /**
     * @param null $billingRecord
     */
    public function setBillingRecord(IsDocumentReadyForBankAccountChangeInterface $billingRecord)
    {
        $this->billingRecord = $billingRecord;
    }

}
