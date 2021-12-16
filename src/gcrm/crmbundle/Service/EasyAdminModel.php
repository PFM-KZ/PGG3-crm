<?php

namespace GCRM\CRMBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;

class EasyAdminModel
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getConfig()
    {
        return $this->container->getParameter('easyadmin.config');
    }

    public function getEntityConfigByEntityName($entity)
    {
        $easyadminConfig = $this->getConfig();
        return $easyadminConfig['entities'][$entity];
    }

    public function getEntityClassByEntityName($entity)
    {
        $entityConfig = $this->getEntityConfigByEntityName($entity);
        return $entityConfig['class'];
    }

    public function getEntityNameByEntityClass($entityClass)
    {
        $easyadminConfig = $this->getConfig();
        foreach ($easyadminConfig['entities'] as $configEntity) {
            if ($configEntity['class'] == $entityClass) {
                return $configEntity['name'];
            }
        }
        return null;
    }

    public function getEntityDirectoryByEntityName($entity)
    {
        $entityConfig = $this->getEntityConfigByEntityName($entity);
        return $entityConfig['directory'];
    }

    public function getEntityDirectoryRelativeByEntityName($entity)
    {
        $entityConfig = $this->getEntityConfigByEntityName($entity);
        return $entityConfig['directoryRelative'];
    }

    public function getEntityGenerateDocumentMethodByEntityName($entity)
    {
        $entityConfig = $this->getEntityConfigByEntityName($entity);
        return $entityConfig['generateDocumentMethod'];
    }

    public function getCloneAsEntityClassByEntityName($entity)
    {
        $entityConfig = $this->getEntityConfigByEntityName($entity);
        $entityConfig = $this->getEntityConfigByEntityName($entityConfig['cloneAsEntity']);
        return $entityConfig['class'];
    }

    public function getCloneAsEntityByEntityName($entity)
    {
        $entityConfig = $this->getEntityConfigByEntityName($entity);
        return $entityConfig['cloneAsEntity'];
    }

    public function getInvoiceTemplateCodeByEntityName($entity)
    {
        $entityConfig = $this->getEntityConfigByEntityName($entity);
        return $entityConfig['invoiceTemplateCode'];
    }

    public function getNumberSettingsCodeByEntityName($entity)
    {
        $entityConfig = $this->getEntityConfigByEntityName($entity);
        return $entityConfig['numberSettingsCode'];
    }

}