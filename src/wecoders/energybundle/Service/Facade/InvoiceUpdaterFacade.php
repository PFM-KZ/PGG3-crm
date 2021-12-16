<?php

namespace Wecoders\EnergyBundle\Service\Facade;

use Doctrine\ORM\EntityManagerInterface;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\ClientAndContractEnergy;
use GCRM\CRMBundle\Entity\ContractEnergy;
use GCRM\CRMBundle\Entity\ContractGas;
use GCRM\CRMBundle\Service\CompanyModel;
use GCRM\CRMBundle\Service\EasyAdminModel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Entity\InvoiceInterface;
use Wecoders\EnergyBundle\Service\ContractAccessor;
use Wecoders\InvoiceBundle\Entity\InvoiceNumberSettings;
use Wecoders\InvoiceBundle\Service\NumberModel;

class InvoiceUpdaterFacade
{
    private $container;
    private $em;
    private $companyModel;
    private $easyAdminModel;
    private $contractAccessor;

    public function __construct(
        ContainerInterface $container,
        EntityManagerInterface $em,
        CompanyModel $companyModel,
        EasyAdminModel $easyAdminModel,
        ContractAccessor $contractAccessor
    )
    {
        $this->companyModel = $companyModel;
        $this->em = $em;
        $this->container = $container;
        $this->easyAdminModel = $easyAdminModel;
        $this->contractAccessor = $contractAccessor;
    }

    public function insertDataByClient(InvoiceInterface $invoice, $flush = true)
    {
        /** @var Client $client */
        $client = $invoice->getClient();
        if (!$client) {
            throw new \RuntimeException('Client not defined');
        }

        if (!$invoice->getType()) {
            throw new \RuntimeException('Type not defined');
        }

        $contract = $this->contractAccessor->manageContractByType($client, $invoice->getType());
        if (!$contract) {
            throw new \RuntimeException('Contract for this client not found');
        }

        if (!$invoice->getBillingPeriodFrom()) {
            throw new \RuntimeException('Billing period from not defined');
        }

        $createdDate = $invoice->getCreatedDate() ?: new \DateTime();

        $invoice->setPpName($contract->getPpName());
        $invoice->setPpEnergy($contract->getPpCodeByDate($createdDate));
        $invoice->setPpZipCode($contract->getPpZipCode());
        $invoice->setPpCity($contract->getPpCity());
        $invoice->setPpStreet($contract->getPpStreet());
        $invoice->setPpHouseNr($contract->getPpHouseNr());
        $invoice->setPpApartmentNr($contract->getPpApartmentNr());
        $invoice->setSellerTariff($contract->getSellerTariffByDate($createdDate));
        $invoice->setDistributionTariff($contract->getDistributionTariffByDate($createdDate));

        $this->setUpNumberData($invoice, $client);

        $invoice->setCreatedDate($createdDate);

        $invoice->setBadgeId($client->getAccountNumberIdentifier()->getNumber());
        $invoice->setClientAccountNumber($client->getBankAccountNumber());

        $invoice->setCreatedIn(($invoice->getCreatedIn() ?: 'Katowice'));
        $invoice->setPaidValue(0);

        $company = $this->companyModel->getCompanyReadyForGenerateBankAccountNumbers();
        $invoice->setSellerTitle($company->getName());
        $invoice->setSellerRegon($company->getRegon());
        $invoice->setSellerNip($company->getNip());
        $invoice->setSellerZipCode($company->getZipcode());
        $invoice->setSellerCity($company->getCity());
        $invoice->setSellerBankName($company->getBankName());
        $invoice->setSellerAddress($company->getAddress());

        $invoice->setClientNip($client->getNip());
        $invoice->setClientPesel($client->getPesel());
        $invoice->setClientFullName($client->getFullName());
        $invoice->setClientZipCode($client->getZipCode());
        $invoice->setClientCity($client->getCity());
        $invoice->setClientStreet($client->getStreet());
        $invoice->setClientHouseNr($client->getHouseNr());
        $invoice->setClientApartmentNr($client->getApartmentNr());

        if ($client->getIsCompany()) {
            $invoice->setClientFullName($client->getCompanyName());
            $invoice->setRecipientCompanyName($client->getToRecipientCompanyName());
            $invoice->setRecipientNip($client->getToRecipientNip());
            $invoice->setRecipientZipCode($client->getToRecipientZipCode());
            $invoice->setRecipientCity($client->getToRecipientCity());
            $invoice->setRecipientStreet($client->getToRecipientStreet());
            $invoice->setRecipientHouseNr($client->getToRecipientHouseNr());
            $invoice->setRecipientApartmentNr($client->getToRecipientApartmentNr());
            $invoice->setPayerCompanyName($client->getToPayerCompanyName());
            $invoice->setPayerNip($client->getToPayerNip());
            $invoice->setPayerZipCode($client->getToPayerZipCode());
            $invoice->setPayerCity($client->getToPayerCity());
            $invoice->setPayerStreet($client->getToPayerStreet());
            $invoice->setPayerHouseNr($client->getToPayerHouseNr());
            $invoice->setPayerApartmentNr($client->getToPayerApartmentNr());
        } else {
            $invoice->setPayerZipCode($client->getToCorrespondenceZipCode());
            $invoice->setPayerCity($client->getToCorrespondenceCity());
            $invoice->setPayerStreet($client->getToCorrespondenceStreet());
            $invoice->setPayerHouseNr($client->getToCorrespondenceHouseNr());
            $invoice->setPayerApartmentNr($client->getToCorrespondenceApartmentNr());
        }

        $this->em->persist($invoice);
        if ($flush) {
            $this->em->flush();
        }
    }

    public function getConfigByClass($class)
    {
        $config = $this->easyAdminModel->getConfig();

        foreach ($config['entities'] as $config) {
            if (!$config['class']) {
                continue;
            }

            if ($config['class'] == $class) {
                return $config;
            }
        }

        return $config;
    }

    /**
     * @param InvoiceInterface $invoice
     * @param $client
     */
    private function setUpNumberData(InvoiceInterface $invoice, Client $client)
    {
        $config = $this->getConfigByClass(get_class($invoice));

        $numberModel = new NumberModel();
        $numberModel->init(
            $this->container->get('kernel')->getRootDir(),
            $this->em,
            $invoice->getCreatedDate()
        );

        /** @var InvoiceNumberSettings $numberStructure */
        $numberStructure = $numberModel->getSettings($config['numberSettingsCode']);
        if (!$numberStructure) {
            die('Opcje generowania numeru nie zostały ustawione.');
        }

        $tokensWithReplacement = [
            [
                'token' => '#id#',
                'replacement' => $client->getAccountNumberIdentifier()->getNumber(), // badge id for example
            ]
        ];
        $generatedNumber = $numberModel->generate($tokensWithReplacement, get_class($invoice), 'number', $config['numberSettingsCode']);
        if (!$generatedNumber) {
            die('Can not generate document number');
        }

        $invoice->setNumber($generatedNumber);
        $invoice->setNumberStructure($numberStructure->getStructure());
        $invoice->setNumberLeadingZeros($numberStructure->getLeadingZeros());
        $invoice->setNumberResetAiAtNewMonth($numberStructure->getResetAiAtNewMonth());
        $invoice->setNumberExcludeAiFromLeadingZeros($numberStructure->getExcludeAiFromLeadingZeros());
    }

}