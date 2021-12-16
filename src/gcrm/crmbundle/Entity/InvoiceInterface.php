<?php

namespace GCRM\CRMBundle\Entity;

interface InvoiceInterface
{
    public function getNumber();
    public function setNumber($number);

    public function getNumberStructure();
    public function setNumberStructure($numberStructure);

    public function getNumberLeadingZeros();
    public function setNumberLeadingZeros($numberLeadingZeros);

    public function getNumberResetAiAtNewMonth();
    public function setNumberResetAiAtNewMonth($resetAiAtNewMonth);


    public function getCreatedDate();
    public function setCreatedDate($createdDate);

    public function getCreatedIn();
    public function setCreatedIn($createdIn);

    public function getDateOfPayment();
    public function setDateOfPayment($dateOfPayment);

    public function getBillingPeriod();
    public function setBillingPeriod($billingPeriod);


    public function getSellerTitle();
    public function setSellerTitle($sellerTitle);

    public function getSellerAddress();
    public function setSellerAddress($sellerAddress);

    public function getSellerZipCode();
    public function setSellerZipCode($sellerZipCode);

    public function getSellerCity();
    public function setSellerCity($sellerCity);

    public function getSellerNip();
    public function setSellerNip($sellerNip);

    public function getSellerBankName();
    public function setSellerBankName($sellerBankName);

    public function getSellerBankAccount();
    public function setSellerBankAccount($sellerBankAccount);


    public function getClientNip();
    public function setClientNip($clientNip);

    public function getClientFullName();
    public function setClientFullName($sellerTitle);

    public function getClientAddress();
    public function setClientAddress($clientAddress);

    public function getClientZipCode();
    public function setClientZipCode($clientZipCode);

    public function getClientCity();
    public function setClientCity($clientCity);


    public function getData();
    public function setData($data);


    public function getSummaryVatValue();
    public function setSummaryVatValue($summaryVatValue);

    public function getSummaryNetValue();
    public function setSummaryNetValue($summaryNetValue);

    public function getSummaryGrossValue();
    public function setSummaryGrossValue($summaryGrossValue);

    public function getIsElectronic();
    public function setIsElectronic($isElectronic);
}

