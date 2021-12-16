<?php

namespace GCRM\CRMBundle\Service\BillingDocument;

interface DocumentInterface
{
    public function getDocumentRowsByClientId($client);
    public function getEntity();
}