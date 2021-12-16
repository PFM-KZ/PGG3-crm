<?php

namespace GCRM\CRMBundle\Service;

use GCRM\CRMBundle\Entity\Company;

class AccountNumberMaker
{
    private $accountNumberIdentifierModel;
    private $accountNumberModel;
    private $companyModel;

    public function __construct(
        AccountNumberIdentifierModel $accountNumberIdentifierModel,
        AccountNumberModel $accountNumberModel,
        CompanyModel $companyModel
    )
    {
        $this->accountNumberIdentifierModel = $accountNumberIdentifierModel;
        $this->accountNumberModel = $accountNumberModel;
        $this->companyModel = $companyModel;
    }

    public function append(AccountNumberInterface $obj)
    {
        $number = $this->accountNumberIdentifierModel->generateNumber();

        $bankAccountNumber = $this->generateBankAccountNumber($number);

        $accountNumberIdentifier = $this->accountNumberIdentifierModel->add($number);

        $obj->setAccountNumberIdentifier($accountNumberIdentifier);
        $obj->setBankAccountNumber($bankAccountNumber);
    }

    public function generateBankAccountNumber($accountNumberIdentifier, $staticTenNumbers = null, $staticFourNumbers = null)
    {
        /** @var Company $companyForBankAccountNumber */
        $companyForBankAccountNumber = $this->companyModel->getCompanyReadyForGenerateBankAccountNumbers();
        if ($staticTenNumbers === null || $staticFourNumbers === null) {
            $staticTenNumbers = $companyForBankAccountNumber->getBankGeneratorStaticPartCodeOne();
            $staticFourNumbers = $companyForBankAccountNumber->getBankGeneratorStaticPartCodeTwo();
        }

        return $this->accountNumberModel->generateBankAccountNumber(
            $staticTenNumbers . $staticFourNumbers,
            $accountNumberIdentifier
        );
    }
}