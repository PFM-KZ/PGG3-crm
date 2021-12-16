<?php

namespace GCRM\CRMBundle\Service;

use GCRM\CRMBundle\Entity\AccountNumberIdentifier;

interface AccountNumberInterface
{
    public function getAccountNumberIdentifier();
    public function setAccountNumberIdentifier(AccountNumberIdentifier $accountNumberIdentifier);

    public function getBankAccountNumber();
    public function setBankAccountNumber($bankAccountNumber);
}