<?php

namespace GCRM\CRMBundle\Entity;

interface ContractAndPriceListInterface
{
    public function getContract();
    public function setContract($contract);

    public function getFromDate();
    public function getPriceList();
}