<?php

namespace Wecoders\EnergyBundle\Command;

use Complex\Exception;
use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\ContractEnergyBase;
use GCRM\CRMBundle\Service\ContractModel;
use GCRM\CRMBundle\Service\EasyAdminModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\LockHandler;
use Wecoders\EnergyBundle\Entity\InvoiceProforma;
use Wecoders\EnergyBundle\Entity\PackageToGenerate;
use Wecoders\EnergyBundle\Entity\PriceList;
use Wecoders\EnergyBundle\Entity\PriceListData;
use Wecoders\EnergyBundle\Entity\PriceListDataAndTariff;
use Wecoders\EnergyBundle\Entity\PriceListDataAndYearWithPrice;
use Wecoders\EnergyBundle\Entity\Tariff;
use Wecoders\EnergyBundle\Service\PackageToGenerateModel;
use Wecoders\InvoiceBundle\Entity\InvoiceTemplate;
use Wecoders\InvoiceBundle\Service\InvoiceModel;
use Wecoders\InvoiceBundle\Service\InvoiceTemplateModel;

class PackageToGenerateStatusGenerate extends Command
{
    /* @var EntityManager */
    private $em;

    private $container;

    private $packageToGenerateModel;

    private $invoiceModel;

    private $energyBundleInvoiceModel;

    private $invoiceTemplateModel;

    private $easyAdminModel;

    private $contractModel;

    public function __construct(ContainerInterface $container, EntityManager $em, PackageToGenerateModel $packageToGenerateModel, InvoiceModel $invoiceModel, \Wecoders\EnergyBundle\Service\InvoiceModel $energyBundleInvoiceModel, InvoiceTemplateModel $invoiceTemplateModel, EasyAdminModel $easyAdminModel, ContractModel $contractModel)
    {
        $this->em = $em;
        $this->container = $container;
        $this->packageToGenerateModel = $packageToGenerateModel;
        $this->invoiceModel = $invoiceModel;
        $this->invoiceTemplateModel = $invoiceTemplateModel;
        $this->easyAdminModel = $easyAdminModel;
        $this->energyBundleInvoiceModel = $energyBundleInvoiceModel;
        $this->contractModel = $contractModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('wecodersenergybundle:package-to-generate-status-generate')
            ->setDescription('Generate documents from package.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // set up lock, so command can be used only in single process to avoid duplicates
        $lock = new LockHandler('package_to_generate_status_generate');
        if (!$lock->lock()) {
            $output->writeln('This command is already running in another process.');
            return 0;
        }

        $em = $this->em;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $kernelRootDir = $this->container->get('kernel')->getRootDir();



        /** @var PackageToGenerate $packageToGenerate */
        $packageToGenerate = $this->packageToGenerateModel->getSingleRecordByStatus(PackageToGenerateModel::STATUS_GENERATE);
        if (!$packageToGenerate) {
            dump('No packages with "generate" status.');
            die;
        }

        // Here is status generate,
        // that means generate documents, and add them as generated
        // next status gonna change
        $documentIds = $packageToGenerate->getDocumentIds();
        $checkedDocumentIds = $packageToGenerate->getCheckedDocumentIds();
        $documentsNotCheckedIds = array_values(array_diff($documentIds, $checkedDocumentIds));
        $documentToCheckId = count($documentsNotCheckedIds) ? $documentsNotCheckedIds[0] : null;
        if (!$documentToCheckId) {
            // all contracts were checked, so all records documents are ready to be generated
            // this is the moment to change status to generate documents
            $packageToGenerate->setStatus(PackageToGenerateModel::STATUS_COMPLETE);
            $em->persist($packageToGenerate);
            $em->flush();
            dump('Status changed to complete');
            dump('Success');
            die;
        }


        $index = 1;
        $em->getConnection()->beginTransaction();
        try {
            $document = $em->getRepository('WecodersEnergyBundle:InvoiceProforma')->find($documentToCheckId);
            if (!$document) {
                die('Dokument nie istnieje');
            }

            $contract = $this->contractModel->getContractByNumber($document->getContractNumber(), [
                'GCRMCRMBundle:ClientAndContractEnergy' => 'GCRMCRMBundle:ContractEnergy',
                'GCRMCRMBundle:ClientAndContractGas' => 'GCRMCRMBundle:ContractGas',
            ]);
            if (!$contract) {
                die('Umowa z podanym numerem na fakturze nie istnieje.');
            }

            // GENERATE INVOICE FILES
            // 3 attempts to generate file, if file not exist after 3 attempts then change status to error

            /** @var InvoiceTemplate $invoiceTemplate */
            $invoiceTemplate = $document->getInvoiceTemplate();
            if (!$invoiceTemplate || !$invoiceTemplate->getFilePath() || !file_exists($this->invoiceTemplateModel->getTemplateAbsolutePath($invoiceTemplate->getFilePath()))) {
                die('Szablon faktury nie istnieje (sprawdź czy rekord faktury ma ustawiony szablon oraz czy rekord szablonu ma wgrany plik).');
            }
            $templateAbsolutePath = $this->invoiceTemplateModel->getTemplateAbsolutePath($invoiceTemplate->getFilePath());

            $directoryRelative = $this->easyAdminModel->getEntityDirectoryRelativeByEntityName('InvoiceProformaEnergy');
            $invoicePath = $this->invoiceModel->fullInvoicePath($kernelRootDir, $document, $directoryRelative);

            $fileGenerated = false;
            for ($i = 0; $i < 3; $i++) {
                $this->energyBundleInvoiceModel->generateInvoiceProforma($document, $invoicePath, $templateAbsolutePath, $contract->getType());
                dump('Generate file attempt.');
                if (file_exists($invoicePath . '.pdf')) {
                    $fileGenerated = true;
                    break;
                }
            }

            if ($fileGenerated) {
                $packageToGenerate->addCheckedDocumentId($document->getId());
                $em->persist($packageToGenerate);
                $em->flush();
            } else {
                throw new Exception('After few attempts file were not generated.');
            }

            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollBack();

            $packageToGenerate->setStatus(PackageToGenerateModel::STATUS_GENERATE_ERROR);
            $em->persist($packageToGenerate);
            $em->flush();

            dump('Error occoured: ' . $e->getMessage());
        }



        $em->clear();
        dump('Success');
        // release lock, so command can be used again
        $lock->release();


        // here can command be safely shoot again if more records exist
        shell_exec('php ' . $kernelRootDir . '/../bin/console wecodersenergybundle:package-to-generate-status-generate');
    }

    private function getCurrentEnergyPricesByPriceListAndTariff(PriceList $priceList, Tariff $tariff)
    {
        // check if tariff exist in price list in current year
        /** @var PriceListData $data */
        $data = $priceList->getPriceListDatas();
        if (!$data) {
            throw new \Exception('Price list data is empty');
        }

        /** @var PriceListData $priceListData */
        $energyPrices = [];
        foreach ($data as $priceListData) {
            $foundTariffSoCanCheckForCurrentYearIfExist = false;

            $tariffTypeCode = $priceListData->getTariffTypeCode();
            if (!$tariffTypeCode) {
                throw new \Exception('Price list data tariff type code is empty');
            }

            /** @var PriceListDataAndTariff $priceListDataAndTariffs */
            $priceListDataAndTariffs = $priceListData->getPriceListDataAndTariffs();
            if (!$priceListDataAndTariffs) {
                throw new \Exception('Price list data and tariffs are empty');
            }

            /** @var PriceListDataAndTariff $priceListDataAndTariff */
            foreach ($priceListDataAndTariffs as $priceListDataAndTariff) {
                /** @var Tariff $itemTariff */
                $itemTariff = $priceListDataAndTariff->getTariff();
                if (!$itemTariff) {
                    continue;
                }
                if ($itemTariff->getId() == $tariff->getId()) {
                    $foundTariffSoCanCheckForCurrentYearIfExist = true;
                    break;
                }
            }

            if ($foundTariffSoCanCheckForCurrentYearIfExist) {
                $priceListDataAndYearWithPrices = $priceListData->getPriceListDataAndYearWithPrices();
                if (!$priceListDataAndYearWithPrices) {
                    throw new \Exception('Price list data and year with prices are empty');
                }

                // search if year match with current year
                $now = new \DateTime();
                $now->format('Y');

                /** @var PriceListDataAndYearWithPrice $priceListDataAndYearWithPrice */
                foreach ($priceListDataAndYearWithPrices as $priceListDataAndYearWithPrice) {
                    $year = $priceListDataAndYearWithPrice->getYear();
                    $grossValue = $priceListDataAndYearWithPrice->getGrossValue();
                    $netValue = $priceListDataAndYearWithPrice->getNetValue();

                    if (!$year || !is_numeric($grossValue) || !is_numeric($netValue)) {
                        throw new \Exception('Price list data and year with prices are not set properly');
                    }

                    if ($year == $now->format('Y')) {
                        $energyPrices[] = [
                            'typeCode' => $tariffTypeCode,
                            'netValue' => $netValue,
                            'grossValue' => $grossValue,
                        ];
                        break;
                    }
                }
            }
        }
        if (!count($energyPrices)) {
            throw new \Exception('Energy prices not found for current price list and tariff');
        }

        return $energyPrices;
    }

}