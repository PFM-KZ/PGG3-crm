<?php

namespace Wecoders\EnergyBundle\Entity;

interface ICollectiveMarkable
{
    public function getIsInInvoiceCollective();
    public function setIsInInvoiceCollective($isInInvoiceCollective);

    public function getInvoiceCollectiveNumber();
    public function setInvoiceCollectiveNumber($number);
}

