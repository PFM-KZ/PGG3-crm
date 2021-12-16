<?php

namespace Wecoders\EnergyBundle\Entity;

interface BillingDocumentInterface
{
    public function getNumber();
    public function getIsNotActual();
    public function setIsNotActual($isNotActual);
    public function getPaidValue();
    public function setPaidValue($paidValue);
    public function setIsPaid($isPaid);
    public function getFrozenValue();
    public function getSummaryGrossValue();
    public function getDateOfPayment();
    public function getIsPaid();
}
