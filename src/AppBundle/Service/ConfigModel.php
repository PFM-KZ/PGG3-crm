<?php

namespace AppBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use GCRM\CRMBundle\Entity\Settings\System;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigModel
{
    private $em;

    private $container;

    function __construct(EntityManagerInterface $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function loadSystemSettings($appParam, $className, $clearDifference = false)
    {
        $dbSettings = $this->em->getRepository($className)->findAll();
        $appSettings = $this->container->getParameter($appParam);
        $settingsArray = array();
        
        foreach($appSettings as $setting) {
            $settingsArray[] = $this->createSetting($className, $setting);
        }

        $added = 0;
        foreach($settingsArray as $setting) {
            if(!$this->settingExists($setting, $dbSettings)) {
                $this->em->persist($setting);
                $this->em->flush();
                $added++;
            }
        }

        $deleted = 0;
        if($clearDifference) {
            $keys = array();
            foreach($settingsArray as $setting) {
                $keys[] = $setting->getName();
            }

            foreach($dbSettings as $setting) {
                if(array_search($setting->getName(), $keys) === false) {
                    $this->em->remove($setting);
                    $this->em->flush();
                    $deleted++;
                }
            }
        }

        return [
            'added' => $added,
            'deleted' => $deleted,
        ];
    }

    private function createSetting($className, array $setting)
    {
        if(!isset($setting['name'])) {
            throw new \Exception('Missing required parameter "name"');
        }
        if(!isset($setting['tooltip'])) {
            throw new \Exception('Missing required parameter "tooltip"');
        }

        $entity = new $className($setting['name'], $setting['tooltip']);

        if(isset($setting['autoload'])) {
            $autoload = $setting['autoload'];
        } else {
            $autoload = false;
        }
        
        $entity->setAutoload($autoload);

        if(isset($setting['value'])) {
            $entity->setValue($setting['value']);
        }

        return $entity;
    }

    private function settingExists($setting, array $settings)
    {
        /** @var System $s */
        foreach($settings as $s) {
            if($s->getName() === $setting->getName()) {
                if($s->getAutoload() !== $setting->getAutoload()) {
                    $s->setAutoload($setting->getAutoload());
                    $this->em->flush($s);
                }
                return true;
            }
        }

        return false;
    }

}