<?php

namespace Wecoders\EnergyBundle\Service\ReadingsFilters;

class Filter
{
    private $records;

    private $isFirstSettlement;

    private $isLastSettlement;

    public function __construct($records, $isFirstSettlement, $isLastSettlement)
    {
        $this->records = $records;
        $this->isFirstSettlement = $isFirstSettlement;
        $this->isLastSettlement = $isLastSettlement;
    }

    public function getIsFirstSettlement()
    {
        return $this->isFirstSettlement;
    }

    public function getIsLastSettlement()
    {
        return $this->isLastSettlement;
    }

    public function getRecords()
    {
        return $this->records;
    }

    public function setRecords($records)
    {
        $this->records = $records;
    }

}