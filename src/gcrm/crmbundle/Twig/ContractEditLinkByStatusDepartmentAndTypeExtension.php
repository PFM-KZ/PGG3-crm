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

class ContractEditLinkByStatusDepartmentAndTypeExtension extends Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('contractEditLinkByStatusDepartmentAndType', array($this, 'contractEditLinkByStatusDepartmentAndType')),
        );
    }

    public function contractEditLinkByStatusDepartmentAndType($contract, $type)
    {
        if (!is_object($contract) || !$type) {
            return null;
        }

        /** @var StatusDepartment $statusDepartment */
        $statusDepartment = $contract->getStatusDepartment();
        if (!$statusDepartment) {
            $statusDepartmentCode = 'finances';
        } else {
            $statusDepartmentCode = $statusDepartment->getCode();
        }

        return '/admin/?entity=Contract' . ucfirst(mb_strtolower($type)) . ucfirst(mb_strtolower($statusDepartmentCode)) . 'Department&action=edit&id=' . $contract->getId();
    }
}