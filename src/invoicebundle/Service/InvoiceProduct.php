<?php

namespace Wecoders\InvoiceBundle\Service;

class InvoiceProduct
{
    private $id;

    private $title;

    private $priceValue;

    private $netValue;

    private $vatPercentage;

    private $grossValue;

    private $unit;

    private $quantity;

    private $excise;

    // key value array
    private $custom = [];

    /**
     * On invoice
     *
     * @var boolean */
    private $isUnique;

    /**
     * Id to check isUnique product or not
     */
    private $originId;

    public function __construct(Helper $helper)
    {
        $this->helper = $helper;
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

    public function getVatValue($precision = 2)
    {
        $vatValue = 0;

        if ($this->netValue > 0 && $this->vatPercentage) {
            $vatValue = number_format(($this->netValue * $this->vatPercentage / 100), $precision, '.', '');
        } elseif (!$this->netValue && $this->grossValue > 0 && $this->vatPercentage) {
            $vatValue = number_format(($this->grossValue - $this->grossValue / ($this->vatPercentage / 100 + 1)), $precision, '.', '');
        }

        return $vatValue;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    public function getNetValue($precision = 2)
    {
        if ($this->netValue > 0) {
            return $this->netValue;
        }

        $netValue = 0;

        if ($this->vatPercentage && $this->grossValue > 0) {
            $netValue = number_format(($this->grossValue / ($this->vatPercentage / 100 + 1)), $precision, '.', '');
        } elseif ($this->grossValue > 0) {
            $netValue = number_format($this->grossValue, $precision);
        }

        return $netValue;
    }

    public function setNetValue($netValue)
    {
        $this->netValue = $netValue;

        return $this;
    }

    public function getVatPercentage()
    {
        return $this->vatPercentage;
    }

    public function setVatPercentage($vatPercentage)
    {
        $this->vatPercentage = $vatPercentage;

        return $this;
    }

    public function getGrossValue($precision = 2)
    {
        if ($this->grossValue > 0) {
            return $this->grossValue;
        }

        $grossValue = 0;

        if ($this->netValue > 0 && $this->vatPercentage) {
            $grossValue = number_format(($this->netValue + $this->netValue * $this->vatPercentage / 100), $precision, '.', '');
        } elseif ($this->netValue > 0) {
            $grossValue = number_format($this->netValue, $precision, '.', '');
        }

        return $grossValue;
    }

    public function setGrossValue($grossValue)
    {
        $this->grossValue = $grossValue;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPriceValue()
    {
        return $this->priceValue;
    }

    /**
     * @param mixed $priceValue
     */
    public function setPriceValue($priceValue)
    {
        $this->priceValue = $priceValue;
    }

    /**
     * @return mixed
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @param mixed $unit
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;
    }

    /**
     * @return mixed
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param mixed $quantity
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * @return mixed
     */
    public function getExcise()
    {
        return $this->excise;
    }

    /**
     * @param mixed $excise
     */
    public function setExcise($excise)
    {
        $this->excise = $excise;
    }

    /**
     * @return array
     */
    public function getCustom()
    {
        return $this->custom;
    }

    /**
     * @param array $custom
     */
    public function setCustom($custom)
    {
        $this->custom = $custom;
    }

    /**
     * @return bool
     */
    public function getIsUnique()
    {
        return $this->isUnique;
    }

    /**
     * @param bool $isUnique
     */
    public function setIsUnique($isUnique)
    {
        $this->isUnique = $isUnique;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOriginId()
    {
        return $this->originId;
    }

    /**
     * @param mixed $originId
     */
    public function setOriginId($originId)
    {
        $this->originId = $originId;

        return $this;
    }
}