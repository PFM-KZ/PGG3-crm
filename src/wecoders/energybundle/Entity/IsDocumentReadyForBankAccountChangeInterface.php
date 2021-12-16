<?php

namespace Wecoders\EnergyBundle\Entity;

interface IsDocumentReadyForBankAccountChangeInterface
{
    public function getBadgeId();
    public function getNumber();
    public function getClient();
}

