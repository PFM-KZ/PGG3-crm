<?php

namespace GCRM\CRMBundle\Twig;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\ClientAndContractGas;
use GCRM\CRMBundle\Entity\ContractGas;
use GCRM\CRMBundle\Entity\StatusContractAuthorization;
use GCRM\CRMBundle\Entity\StatusDepartment;
use GCRM\CRMBundle\Service\StatusClient;
use Twig_Extension;

class StatusContractExtension extends Twig_Extension
{
    /** @var EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('contractStatus', array($this, 'contractStatus')),
        );
    }

    public function contractStatus($contract)
    {
        $status = null;

        if (!$contract) {
            return null;
        }

        /** @var StatusDepartment $currentStatusDepartment */
        $currentStatusDepartment = $contract->getStatusDepartment();

        if (!$currentStatusDepartment) {
            return null;
        }

        if ($currentStatusDepartment->getCode() == 'verification') {
            $status = $contract->getStatusContractVerification() ? $contract->getStatusContractVerification() : $contract->getStatusContractAuthorization();
        } elseif ($currentStatusDepartment->getCode() == 'administration') {
            $status = $contract->getStatusContractAdministration() ? $contract->getStatusContractAdministration() : $contract->getStatusContractVerification();
        } elseif ($currentStatusDepartment->getCode() == 'control') {
            $status = $contract->getStatusContractControl() ? $contract->getStatusContractControl() : $contract->getStatusContractAdministration();
        } elseif ($currentStatusDepartment->getCode() == 'process') {
            $status = $contract->getStatusContractProcess() ? $contract->getStatusContractProcess() : $contract->getStatusContractControl();
        } elseif ($currentStatusDepartment->getCode() == 'finances') {
            $status = $contract->getStatusContractFinances() ? $contract->getStatusContractFinances() : $contract->getStatusContractProcess();
        }

        return $status;
    }
}