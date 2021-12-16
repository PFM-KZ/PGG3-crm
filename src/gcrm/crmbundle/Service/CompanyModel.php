<?php

namespace GCRM\CRMBundle\Service;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Company;

class CompanyModel
{
    /** @var  EntityManager */
    private $em;

    private $entity = 'GCRMCRMBundle:Company';

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getCompanyReadyForGenerateBankAccountNumbers()
    {
        $companies = $this->em->getRepository($this->entity)->findBy([
            'isActive' => true,
            'enableBankAccountGenerator' => true,
        ]);

        if (!$companies || ($companies && count($companies) > 1)) {
            return null;
        }

        /** @var Company $company */
        $company = $companies[0];

        $part1 = $company->getBankGeneratorStaticPartCodeOne();
        if (!$part1 || ($part1 && mb_strlen($part1) != 8) || ($part1 && !is_numeric($part1))) {
            return null;
        }

        $part2 = $company->getBankGeneratorStaticPartCodeTwo();
        if (!$part2 || ($part2 && mb_strlen($part2) != 4) || ($part2 && !is_numeric($part2))) {
            return null;
        }

        return $company;
    }

    public function getCompanyByBankAccountNumber($number)
    {
        return $this->em->getRepository($this->entity)->findOneBy(['bankAccountNumber' => $number]);
    }
}