<?php

namespace GCRM\CRMBundle\Service\Settings;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class System
{
    const ENTITY = 'GCRMCRMBundle:Settings\System';

    const LOGO_DOCUMENT_DEFAULT = 'logo_document_default';

    private $em;

    private $container;

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function getRecord($name)
    {
        return $this->em->getRepository(self::ENTITY)->findOneBy(['name' => $name]);
    }

    public function getAbsoluteFilePath($filename)
    {
        return $this->getAbsoluteDirPath() . '/' . $filename;
    }

    public function getAbsoluteDirPath()
    {
        return $this->container->getParameter('vich.path.absolute.system_settings');
    }

}