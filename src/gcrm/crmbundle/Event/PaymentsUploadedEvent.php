<?php

namespace GCRM\CRMBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class PaymentsUploadedEvent extends Event
{
    protected $payments = [];

    public function __construct($payments)
    {
        $this->payments = $payments;
    }

    /**
     * @return array
     */
    public function getPayments()
    {
        return $this->payments;
    }

    /**
     * @param array $payments
     */
    public function setPayments($payments)
    {
        $this->payments = $payments;

        return $this;
    }

}
