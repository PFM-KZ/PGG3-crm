<?php

namespace GCRM\CRMBundle\Service\PaymentImporter;

use Doctrine\ORM\EntityManagerInterface;
use GCRM\CRMBundle\Service\PaymentImporterModel;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Service\SpreadsheetReader;

class PaymentImporterFactory
{
    private $spreadsheetReader;
    private $em;
    private $kernelRootDir;
    private $container;

    public function __construct(EntityManagerInterface $em, SpreadsheetReader $spreadsheetReader, ContainerInterface $container)
    {
        $this->spreadsheetReader = $spreadsheetReader;
        $this->em = $em;
        $this->container = $container;
        $this->kernelRootDir = $container->get('kernel')->getRootDir();
    }

    public function create($bankType)
    {
        if ($bankType == PaymentImporterModel::BANK_TYPE_ING) {
            return new Ing($this->em, $this->spreadsheetReader, $this->container, $this->kernelRootDir);
        } elseif ($bankType == PaymentImporterModel::BANK_TYPE_SANTANDER) {
            return new Santander($this->em, $this->spreadsheetReader, $this->kernelRootDir);
        } elseif ($bankType == PaymentImporterModel::BANK_TYPE_SANTANDER_V2) {
            return new SantanderV2($this->em, $this->spreadsheetReader, $this->kernelRootDir);
        } elseif ($bankType == PaymentImporterModel::BANK_TYPE_PEKAO) {
            return new Pekao($this->em, $this->spreadsheetReader, $this->kernelRootDir);
        }

        throw new InvalidArgumentException('Unknown bank type given');
    }
}