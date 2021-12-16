<?php

namespace GCRM\CRMBundle\Service;

use GCRM\CRMBundle\Entity\Client;

class StatusClient
{
    const STATUS_POSITIVE = 'positive';
    const STATUS_NEGATIVE = 'negative';
    const STATUS_WARNING = 'warning';

    public function checkClientStatus(Client $client)
    {

        return 'positive';
    }
}