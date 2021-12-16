<?php

namespace Wecoders\EnergyBundle\Entity;

interface InvoiceInterface
{
    public function getType();
    public function setType($type);

    public function getNumber();
    public function setNumber($number);

    public function getInvoiceTemplate();
    public function setInvoiceTemplate($invoiceTemplate);

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

    public function getBadgeId();
    public function setBadgeId($badgeId);




    public function getClientPesel();
    public function setClientPesel($clientPesel);

    public function getClientFullName();
    public function setClientFullName($sellerTitle);

    public function getClientNip();
    public function setClientNip($clientNip);

    public function getClientZipCode();
    public function setClientZipCode($clientZipCode);

    public function getClientCity();
    public function setClientCity($clientCity);

    public function getClientStreet();
    public function setClientStreet($clientStreet);

    public function getClientHouseNr();
    public function setClientHouseNr($clientHouseNr);

    public function getClientApartmentNr();
    public function setClientApartmentNr($clientApartmentNr);



    public function getRecipientCompanyName();
    public function setRecipientCompanyName($sellerTitle);

    public function getRecipientNip();
    public function setRecipientNip($clientNip);

    public function getRecipientZipCode();
    public function setRecipientZipCode($clientZipCode);

    public function getRecipientCity();
    public function setRecipientCity($clientCity);

    public function getRecipientStreet();
    public function setRecipientStreet($clientStreet);

    public function getRecipientHouseNr();
    public function setRecipientHouseNr($clientHouseNr);

    public function getRecipientApartmentNr();
    public function setRecipientApartmentNr($clientApartmentNr);



    public function getPayerCompanyName();
    public function setPayerCompanyName($sellerTitle);

    public function getPayerNip();
    public function setPayerNip($clientNip);

    public function getPayerZipCode();
    public function setPayerZipCode($clientZipCode);

    public function getPayerCity();
    public function setPayerCity($clientCity);

    public function getPayerStreet();
    public function setPayerStreet($clientStreet);

    public function getPayerHouseNr();
    public function setPayerHouseNr($clientHouseNr);

    public function getPayerApartmentNr();
    public function setPayerApartmentNr($clientApartmentNr);




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

    public function getPpEnergy();
    public function setPpEnergy($ppEnergy);

    public function getPpCity();
    public function setPpCity($ppCity);

    public function getPpStreet();
    public function setPpStreet($ppStreet);

    public function getPpZipCode();
    public function setPpZipCode($ppZipCode);

    public function getPpHouseNr();
    public function setPpHouseNr($ppHouseNr);

    public function getPpApartmentNr();
    public function setPpApartmentNr($ppApartmentNr);

    public function getBillingPeriodFrom();
    public function setBillingPeriodFrom($billingPeriodFrom);

    public function getBillingPeriodTo();
    public function setBillingPeriodTo($billingPeriodTo);

    public function getContractNumber();
    public function setContractNumber($contractNumber);

    public function getBalanceBeforeInvoice();
    public function setBalanceBeforeInvoice($balanceBeforeInvoice);

}

