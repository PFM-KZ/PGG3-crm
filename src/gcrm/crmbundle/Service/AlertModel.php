<?php

namespace GCRM\CRMBundle\Service;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Alert;

class AlertModel
{
    const CODE_INFO = 'INFO';
    const CODE_NOTICE = 'NOTICE';
    const CODE_WARNING = 'WARNING';
    const CODE_CRITICAL = 'CRITICAL';

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function add($code, $title, $content = null)
    {
        $alert = new Alert();
        $alert->setCode($code);
        $alert->setTitle($title);
        $alert->setContent($content);

        $this->em->persist($alert);
        $this->em->flush();
    }
}