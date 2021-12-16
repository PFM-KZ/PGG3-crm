<?php

namespace Wecoders\EnergyBundle\Service;

class InvoiceCollectiveItem
{
    private $id;
    private $number;
    private $pp;
    private $netValue;
    private $vatPercentage;
    private $vatValue;
    private $grossValue;
    private $consumption;
    private $exciseValue;

    public function hydrateToArray()
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'pp' => $this->pp,
            'netValue' => $this->netValue,
            'vatPercentage' => $this->vatPercentage,
            'vatValue' => $this->vatValue,
            'grossValue' => $this->grossValue,
            'consumption' => $this->consumption,
            'exciseValue' => $this->exciseValue,
        ];
    }

    public function __construct(
        $id,
        $number,
        $pp,
        $netValue,
        $vatPercentage,
        $vatValue,
        $grossValue,
        $consumption,
        $exciseValue
    )
    {
        $this->id = $id;
        $this->number = $number;
        $this->pp = $pp;
        $this->netValue = $netValue;
        $this->vatPercentage = $vatPercentage;
        $this->vatValue = $vatValue;
        $this->grossValue = $grossValue;
        $this->consumption = $consumption;
        $this->exciseValue = $exciseValue;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param mixed $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * @return mixed
     */
    public function getPp()
    {
        return $this->pp;
    }

    /**
     * @param mixed $pp
     */
    public function setPp($pp)
    {
        $this->pp = $pp;
    }

    /**
     * @return mixed
     */
    public function getNetValue()
    {
        return $this->netValue;
    }

    /**
     * @param mixed $netValue
     */
    public function setNetValue($netValue)
    {
        $this->netValue = $netValue;
    }

    /**
     * @return mixed
     */
    public function getVatPercentage()
    {
        return $this->vatPercentage;
    }

    /**
     * @param mixed $vatPercentage
     */
    public function setVatPercentage($vatPercentage)
    {
        $this->vatPercentage = $vatPercentage;
    }

    /**
     * @return mixed
     */
    public function getVatValue()
    {
        return $this->vatValue;
    }

    /**
     * @param mixed $vatValue
     */
    public function setVatValue($vatValue)
    {
        $this->vatValue = $vatValue;
    }

    /**
     * @return mixed
     */
    public function getGrossValue()
    {
        return $this->grossValue;
    }

    /**
     * @param mixed $grossValue
     */
    public function setGrossValue($grossValue)
    {
        $this->grossValue = $grossValue;
    }

    /**
     * @return mixed
     */
    public function getConsumption()
    {
        return $this->consumption;
    }

    /**
     * @param mixed $consumption
     */
    public function setConsumption($consumption)
    {
        $this->consumption = $consumption;
    }

    /**
     * @return mixed
     */
    public function getExciseValue()
    {
        return $this->exciseValue;
    }

    /**
     * @param mixed $exciseValue
     */
    public function setExciseValue($exciseValue)
    {
        $this->exciseValue = $exciseValue;
    }

}