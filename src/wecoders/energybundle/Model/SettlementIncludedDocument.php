<?php

namespace Wecoders\EnergyBundle\Model;

class SettlementIncludedDocument
{
    private $documentNumber;

    private $netValue;

    private $vatValue;

    private $grossValue;

    private $exciseValue;

    private $billingPeriodFrom;

    private $billingPeriodTo;

    public function create(
        $documentNumber,
        $netValue,
        $vatValue,
        $grossValue,
        $exciseValue,
        \DateTime $billingPeriodFrom,
        \DateTime $billingPeriodTo
    )
    {
        $this->documentNumber = $documentNumber;
        $this->netValue = $netValue;
        $this->vatValue = $vatValue;
        $this->grossValue = $grossValue;
        $this->exciseValue = $exciseValue;
        $this->billingPeriodFrom = $billingPeriodFrom->setTime(0, 0);
        $this->billingPeriodTo = $billingPeriodTo->setTime(0, 0);

        return $this;
    }

    public function hydrateToArray()
    {
        return $item = [
            'documentNumber' => $this->documentNumber,
            'netValue' => $this->netValue,
            'vatValue' => $this->vatValue,
            'grossValue' => $this->grossValue,
            'exciseValue' => $this->exciseValue,
            'billingPeriodFrom' => $this->billingPeriodFrom,
            'billingPeriodTo' => $this->billingPeriodTo,
        ];
    }

    /**
     * @return mixed
     */
    public function getDocumentNumber()
    {
        return $this->documentNumber;
    }

    /**
     * @return mixed
     */
    public function getNetValue()
    {
        return $this->netValue;
    }

    /**
     * @return mixed
     */
    public function getVatValue()
    {
        return $this->vatValue;
    }

    /**
     * @return mixed
     */
    public function getGrossValue()
    {
        return $this->grossValue;
    }

    /**
     * @return mixed
     */
    public function getExciseValue()
    {
        return $this->exciseValue;
    }

    /**
     * @return mixed
     */
    public function getBillingPeriodFrom()
    {
        return $this->billingPeriodFrom;
    }

    /**
     * @return mixed
     */
    public function getBillingPeriodTo()
    {
        return $this->billingPeriodTo;
    }

    /**
     * @param mixed $documentNumber
     */
    public function setDocumentNumber($documentNumber)
    {
        $this->documentNumber = $documentNumber;
    }

    /**
     * @param mixed $netValue
     */
    public function setNetValue($netValue)
    {
        $this->netValue = $netValue;
    }

    /**
     * @param mixed $vatValue
     */
    public function setVatValue($vatValue)
    {
        $this->vatValue = $vatValue;
    }

    /**
     * @param mixed $grossValue
     */
    public function setGrossValue($grossValue)
    {
        $this->grossValue = $grossValue;
    }

    /**
     * @param mixed $exciseValue
     */
    public function setExciseValue($exciseValue)
    {
        $this->exciseValue = $exciseValue;
    }

    /**
     * @param mixed $billingPeriodFrom
     */
    public function setBillingPeriodFrom($billingPeriodFrom)
    {
        $this->billingPeriodFrom = $billingPeriodFrom;
    }

    /**
     * @param mixed $billingPeriodTo
     */
    public function setBillingPeriodTo($billingPeriodTo)
    {
        $this->billingPeriodTo = $billingPeriodTo;
    }

}