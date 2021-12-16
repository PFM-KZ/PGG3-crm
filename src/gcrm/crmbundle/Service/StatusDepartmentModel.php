<?php

namespace GCRM\CRMBundle\Service;

use Doctrine\ORM\EntityManager;

class StatusDepartmentModel
{
    const ENTITY = 'GCRMCRMBundle:StatusDepartment';

    const DEPARTMENT_AUTHORIZATION_CODE = 'authorization';
    const DEPARTMENT_VERIFICATION_CODE = 'verification';
    const DEPARTMENT_ADMINISTRATION_CODE = 'administration';
    const DEPARTMENT_CONTROL_CODE = 'control';
    const DEPARTMENT_PROCESS_CODE = 'process';
    const DEPARTMENT_FINANCES_CODE = 'finances';

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getRecords()
    {
        return $this->em->getRepository(self::ENTITY)->findAll();
    }

    public function getRecordByCode($code)
    {
        return $this->em->getRepository(self::ENTITY)->findOneBy(['code' => $code]);
    }
}