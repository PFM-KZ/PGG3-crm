<?php

namespace GCRM\CRMBundle\Service;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Company;

class ModulesModel
{
    /** @var  EntityManager */
    private $em;

    /** @var  CompanyModel */
    private $companyModel;

    public function __construct(EntityManager $em, CompanyModel $companyModel)
    {
        $this->em = $em;
        $this->companyModel = $companyModel;
    }

    /** Enable only if company bank data is set properly and single bank is active with active functionality  */
    public function isEnabledBankAccountGeneratorFunctionality()
    {
        /** @var Company $company */
        $company = $this->companyModel->getCompanyReadyForGenerateBankAccountNumbers();

        if ($company) {
            return true;
        }

        return false;
    }
}