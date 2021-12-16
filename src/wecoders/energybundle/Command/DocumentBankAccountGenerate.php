<?php

namespace Wecoders\EnergyBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\ContractEnergyBase;
use GCRM\CRMBundle\Service\ClientModel;
use GCRM\CRMBundle\Service\Settings\System;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\LockHandler;
use Wecoders\EnergyBundle\Entity\Brand;
use Wecoders\EnergyBundle\Entity\DocumentBankAccountChange;
use Wecoders\EnergyBundle\Service\ContractAccessor;
use Wecoders\EnergyBundle\Service\DocumentBankAccountChangeModel;
use Wecoders\EnergyBundle\Service\InvoiceModel;

class DocumentBankAccountGenerate extends Command
{
    /* @var EntityManager */
    private $em;

    private $container;

    private $invoiceModel;

    private $documentBankAccountChangeModel;

    private $systemSettings;

    private $contractAccessor;

    private $clientModel;

    public function __construct(
        ContainerInterface $container,
        EntityManager $em,
        InvoiceModel $invoiceModel,
        DocumentBankAccountChangeModel $documentBankAccountChangeModel,
        System $systemSettings,
        ContractAccessor $contractAccessor,
        ClientModel $clientModel
    )
    {
        $this->em = $em;
        $this->container = $container;
        $this->invoiceModel = $invoiceModel;
        $this->documentBankAccountChangeModel = $documentBankAccountChangeModel;
        $this->systemSettings = $systemSettings;
        $this->contractAccessor = $contractAccessor;
        $this->clientModel = $clientModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('wecodersenergybundle:document-bank-account-generate')
            ->setDescription('Generate change bank account info documents.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // set up lock, so command can be used only in single process to avoid duplicates
        $lock = new LockHandler('document_bank_account_generate');
        if (!$lock->lock()) {
            $output->writeln('This command is already running in another process.');
            return 0;
        }

        $em = $this->em;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $kernelRootDir = $this->container->get('kernel')->getRootDir();

        /** @var DocumentBankAccountChange $documentBankAccountChange */
        $documentBankAccountChange = $this->documentBankAccountChangeModel->getRecordToGenerate();
        if (!$documentBankAccountChange) {
            dump('No documents to generate');
            die;
        }

        /** @var \GCRM\CRMBundle\Entity\Settings\System $systemSettingTemplate */
        $systemSettingTemplate = $this->systemSettings->getRecord('change_bank_account_document');
        if (!$systemSettingTemplate) {
            dump('No setting with template');
            die;
        }

        $templateAbsolutePath = $this->documentBankAccountChangeModel->getTemplatePath($systemSettingTemplate->getFilePath());
        if (!$templateAbsolutePath || !file_exists($templateAbsolutePath)) {
            dump('Template file is not uploaded');
            die;
        }

        /** @var Client $client */
        $client = $this->clientModel->getClientByBadgeId($documentBankAccountChange->getBadgeId());
        if (!$client) {
            dump('Client does not exist');
            die;
        }

        /** @var ContractEnergyBase $contract */
        $contract = $this->contractAccessor->accessContractBy('id', $client->getId(), 'client');
        if (!$contract) {
            dump('No contract found');
            die;
        }

        $logoAbsolutePath = $this->getLogoAbsolutePath($contract->getBrand());

        $fileGenerated = false;
        $documentPath = $this->documentBankAccountChangeModel->getDirPath() . '/' . $documentBankAccountChange->getBadgeId();
        for ($i = 0; $i < 5; $i++) {
            $this->documentBankAccountChangeModel->generateDocument(
                $documentBankAccountChange,
                $client,
                $contract->getContractNumber(),
                $contract->getType(),
                $documentPath,
                $templateAbsolutePath,
                $logoAbsolutePath
            );
            dump('Generate file attempt.');
            if (file_exists($documentPath . '.pdf')) {
                dump('Generated: ' . $documentPath);
                $fileGenerated = true;
                break;
            }
        }

        if ($fileGenerated) {
            $documentBankAccountChange->setFilePath($documentPath . '.pdf');
            $em->persist($documentBankAccountChange);
            $em->flush();
        } else {
            throw new \Exception('After few attempts file were not generated.');
        }

        $em->clear();
        dump('Success');
        // release lock, so command can be used again
        $lock->release();

        // here can command be safely shoot again if more records exist
        shell_exec('php ' . $kernelRootDir . '/../bin/console wecodersenergybundle:document-bank-account-generate');
    }

    private function getLogoFilenameDefault()
    {
        return $this->systemSettings->getRecord(System::LOGO_DOCUMENT_DEFAULT)->getFilePath();
    }

    public function getLogoAbsolutePath($brand = null)
    {
        $result = null;

        if ($brand && $brand instanceof Brand) {
            $result = $this->getLogoAbsolutePathByBrand($brand);
        }

        // fall back to default if brand logo is not defined
        if (!$result) {
            $result = $this->getLogoAbsolutePathDefault();
        }

        if (!file_exists($result)) {
            throw new \Exception('Wystąpił błąd - plik nie został wygenerowany. Nie znaleziono pliku logo. Sprawdź czy został wgrany i spróbuj ponownie.');
        }

        return $result;
    }

    private function getLogoAbsolutePathByBrand(Brand $brand)
    {
        $filename = $brand->getFilePath();
        if (!$filename) {
            return null;
        }

        return $this->container->get('kernel')->getRootDir() . '/../web' . $this->container->getParameter('vich.path.relative.province') . '/' . $filename;
    }

    private function getLogoAbsolutePathDefault()
    {
        $filename = $this->getLogoFilenameDefault();
        if (!$filename) {
            return null;
        }

        return $this->container->get('kernel')->getRootDir() . '/../web' . $this->container->getParameter('vich.path.relative.system_settings') . '/' . $filename;
    }

}