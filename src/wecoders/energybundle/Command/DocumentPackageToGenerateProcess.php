<?php

namespace Wecoders\EnergyBundle\Command;

use AppBundle\Service\PdfHelper;
use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\ClientAndContract;
use GCRM\CRMBundle\Entity\ContractEnergyBase;
use GCRM\CRMBundle\Service\BillingDocument\Initializer;
use GCRM\CRMBundle\Service\ClientModel;
use GCRM\CRMBundle\Service\DistributorModel;
use GCRM\CRMBundle\Service\EasyAdminModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\LockHandler;
use Wecoders\EnergyBundle\Entity\CustomDocumentTemplate;
use Wecoders\EnergyBundle\Entity\CustomDocumentTemplateAndDocument;
use Wecoders\EnergyBundle\Entity\DebitNotePackageRecord;
use Wecoders\EnergyBundle\Entity\DocumentPackageToGenerate;
use Wecoders\EnergyBundle\Entity\DocumentPackageToGenerateRecord;
use Wecoders\EnergyBundle\Entity\EnergyData;
use Wecoders\EnergyBundle\Entity\InvoiceBase;
use Wecoders\EnergyBundle\Service\ContractAccessor;
use Wecoders\EnergyBundle\Service\CustomDocumentTemplateModel;
use Wecoders\EnergyBundle\Service\DocumentPackageToGenerateModel;
use Wecoders\EnergyBundle\Service\DocumentPackageToGenerateRecordModel;
use Wecoders\EnergyBundle\Service\InvoiceModel;
use Wecoders\EnergyBundle\Service\PriceListModel;
use Wecoders\EnergyBundle\Service\SettlementModel;
use Wecoders\EnergyBundle\Service\TariffModel;
use Wecoders\InvoiceBundle\Service\Helper;
use Wecoders\InvoiceBundle\Service\InvoiceData;
use Wecoders\InvoiceBundle\Service\InvoiceProduct;
use Wecoders\InvoiceBundle\Service\InvoiceProductGroup;

class DocumentPackageToGenerateProcess extends Command
{
    /* @var EntityManager */
    private $em;

    private $container;

    private $invoiceModel;

    private $initializer;

    private $settlementModel;

    private $easyAdminModel;

    private $documentPackageToGenerateModel;

    private $documentPackageToGenerateRecordModel;

    private $customDocumentTemplateModel;

    private $pdfHelper;

    private $contractAccessor;

    private $distributorModel;

    private $priceListModel;

    public function __construct(
        ContainerInterface $container,
        EntityManager $em,
        SettlementModel $settlementModel,
        InvoiceModel $invoiceModel,
        Initializer $initializer,
        EasyAdminModel $easyAdminModel,
        DocumentPackageToGenerateModel $documentPackageToGenerateModel,
        DocumentPackageToGenerateRecordModel $documentPackageToGenerateRecordModel,
        CustomDocumentTemplateModel $customDocumentTemplateModel,
        PdfHelper $pdfHelper,
        ClientModel $clientModel,
        ContractAccessor $contractAccessor,
        PriceListModel $priceListModel,
        DistributorModel $distributorModel
    )
    {
        $this->em = $em;
        $this->container = $container;
        $this->invoiceModel = $invoiceModel;
        $this->initializer = $initializer;
        $this->settlementModel = $settlementModel;
        $this->easyAdminModel = $easyAdminModel;
        $this->documentPackageToGenerateModel = $documentPackageToGenerateModel;
        $this->documentPackageToGenerateRecordModel = $documentPackageToGenerateRecordModel;
        $this->customDocumentTemplateModel = $customDocumentTemplateModel;
        $this->pdfHelper = $pdfHelper;
        $this->contractAccessor = $contractAccessor;
        $this->priceListModel = $priceListModel;
        $this->distributorModel = $distributorModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('wecodersenergybundle:document-package-to-generate-process')
            ->setDescription('Process documents.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var LockHandler $lock */
        $lock = $this->setUpLock($output);

        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $package = $this->getPackageToProcess();

        /** @var DocumentPackageToGenerateRecord $actualProcessedRecord */
        $actualProcessedRecord = $this->getActualProcessedRecord($package);
        if (!$actualProcessedRecord) {
            if ($this->allRecordsProcessed($package)) {
                $this->resetPackageStatus($package);
            }
            dump('No records to process');
            die;
        }

        $this->em->getConnection()->beginTransaction();
        try {
            if ($package->getType() == DocumentPackageToGenerateModel::TYPE_CORRECTION) {
                /** @var InvoiceBase $correctionObject */
                $correctionObject = $this->documentPackageToGenerateRecordModel->createCorrectionObject($package, $actualProcessedRecord);
                $clientAndContract = $this->contractAccessor->fetchClientAndContract($correctionObject->getContractNumber(), $correctionObject->getClient());

                /** @var ContractEnergyBase $contract */
                $contract = $clientAndContract->getContract();


                $correctionObject->setSellerTariff($contract->getSellerTariffByDate($correctionObject->getBillingPeriodFrom()));
                $correctionObject->setDistributionTariff($contract->getDistributionTariffByDate($correctionObject->getBillingPeriodFrom()));

//                $params = unserialize($package->getParams());
//                if ($params && count($params)) {
//                    foreach ($params as $key => $value) {
//                        if ($key == 'x') {
//                        }
//                    }
//                }

                $priceList = $contract->getPriceListByDate($correctionObject->getBillingPeriodFrom());
                $sellerTariff = $contract->getSellerTariffByDate($correctionObject->getBillingPeriodFrom());
                $energyPrices = $this->priceListModel->getCurrentEnergyPricesByPriceListAndTariff($priceList, $sellerTariff, $correctionObject->getBillingPeriodFrom(), $contract->getType());
                $energyPrices = $this->distributorModel->filterEnergyPricesByDistributorTableData($energyPrices, $sellerTariff->getCode(), $contract->getDistributorObject());

                // recalculate proforma
                $correctionObject = $this->recalculate(
                    $correctionObject,
                    $clientAndContract,
                    $correctionObject->getBillingPeriodFrom(),
                    $correctionObject->getDistributionTariff(),
                    $correctionObject->getBillingPeriodTo(),
                    $energyPrices,
                    $correctionObject->getExciseValue()
                );

                $this->em->persist($correctionObject);
                $this->em->flush();

                $actualProcessedRecord->setGeneratedDocumentId($correctionObject->getId());
                $actualProcessedRecord->setStatus(DocumentPackageToGenerateRecordModel::STATUS_WAITING_TO_GENERATE);
            } elseif ($package->getType() == DocumentPackageToGenerateModel::TYPE_CUSTOM_DOCUMENT) {
                /** @var CustomDocumentTemplate $customDocumentTemplate */
                $customDocumentTemplate = $package->getCustomDocumentTemplate();

                if (!$customDocumentTemplate) {
                    throw new \Exception('Template not found');
                }

                $customDocumentTemplateAndDocuments = $customDocumentTemplate->getCustomDocumentTemplateAndDocuments();

                $filesToMerge = [];

                $outputDirPath = $this->documentPackageToGenerateRecordModel->getAbsolutePackageDirPath($package, $actualProcessedRecord);

                /** @var CustomDocumentTemplateAndDocument $customDocumentTemplateAndDocument */
                foreach ($customDocumentTemplateAndDocuments as $customDocumentTemplateAndDocument) {
                    $client = $actualProcessedRecord->getClient();
                    if (!$client) {
                        throw new \RuntimeException('Client not found.');
                    }

                    /** @var ContractEnergyBase $contract */
                    $contract = $this->contractAccessor->accessContractBy('id', $client->getId(), 'client');
                    if (!$contract) {
                        throw new \RuntimeException('Contract not found.');
                    }

                    $documentTemplateDocumentFilename = $customDocumentTemplateAndDocument->getFilePath();
                    if (!$documentTemplateDocumentFilename) {
                        throw new \RuntimeException('Filename not found.');
                    }

                    $documentTemplateDocumentFilepath = $this->customDocumentTemplateModel->getAbsoluteFilePath($documentTemplateDocumentFilename);
                    if (!file_exists($documentTemplateDocumentFilepath)) {
                        throw new \RuntimeException('File not found.');
                    }

                    $extension = $this->customDocumentTemplateModel->getExtension($documentTemplateDocumentFilepath);

                    if ($extension == CustomDocumentTemplateModel::EXTENSION_DOCX) {
                        $fileGenerated = false;

                        $filename = $package->getId() . '.docx';
                        $pdfPath = null;
                        for ($i = 0; $i < 3; $i++) {
                            $this->customDocumentTemplateModel->generateDocxFile(
                                $actualProcessedRecord->getClient(),
                                $contract,
                                $documentTemplateDocumentFilepath,
                                $filename,
                                $outputDirPath
                            );

                            $pdfPath = $outputDirPath . '/' . $package->getId() . '.pdf';
                            if (file_exists($pdfPath)) {
                                $fileGenerated = true;
                                break;
                            }
                        }

                        if (!$fileGenerated) {
                            throw new \Exception('After few attempts file were not generated.');
                        }

                        $filesToMerge[] = $pdfPath;
                    } else {
                        $filesToMerge[] = $documentTemplateDocumentFilepath;
                    }
                }

                $this->pdfHelper->mergePdfFiles($filesToMerge, $outputDirPath . '/' . $actualProcessedRecord->getId() . DocumentPackageToGenerateRecordModel::MERGED_FILENAME_POSTFIX);
                if (!file_exists($outputDirPath . '/' . $actualProcessedRecord->getId() . DocumentPackageToGenerateRecordModel::MERGED_FILENAME_POSTFIX)) {
                    throw new \Exception('Merged file not found.');
                }

                $actualProcessedRecord->setStatus(DocumentPackageToGenerateRecordModel::STATUS_COMPLETE);
            } else {
                throw new \Exception('Unknown package type');
            }

            $this->em->persist($actualProcessedRecord);
            $this->em->flush();

            $this->em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();

            /** @var DebitNotePackageRecord $reFetchedPackageRecord*/
            $reFetchedPackageRecord = $this->documentPackageToGenerateRecordModel->getRecord($actualProcessedRecord->getId());
            $reFetchedPackageRecord->setErrorMessage($e->getMessage() . ' - on line: ' . $e->getLine());
            $reFetchedPackageRecord->setStatus(DocumentPackageToGenerateRecordModel::STATUS_PROCESS_ERROR);
            $this->em->persist($reFetchedPackageRecord);
            $this->em->flush();
        }

        $this->em->clear();
        $this->em->getConnection()->close();
        dump('Success');

        $lock->release();

        $kernelRootDir = $this->container->get('kernel')->getRootDir();
        // here can command be safely shoot again if more records exist
        shell_exec('php ' . $kernelRootDir . '/../bin/console wecodersenergybundle:document-package-to-generate-process');
    }

    public function recalculate(
        $document,
        $clientAndContract,
        $billingPeriodFrom,
        $distributionTariff,
        $billingPeriodTo,
        $energyPrices,
        $excise
    )
    {
        $contract = $clientAndContract->getContract();
        $records = [];
        $recordsGroupedByAreas = [];

        if ($contract->getType() == 'GAS') {
            $recordsGroupedByAreas[""] = [];

            $recordFrom = new EnergyData();
            $recordFrom->setTariff($distributionTariff);
            $recordFrom->setBillingPeriodFrom(null);
            $recordFrom->setBillingPeriodTo($billingPeriodFrom);
            $recordFrom->setConsumptionKwh(0);
            $recordFrom->setCalculatedConsumptionKwh(0);
            $recordFrom->setStateEnd(0);
            $recordFrom->setCode($contract->getOsd()->getOption());
            $recordFrom->setDeviceId($contract->getGasMeterFabricNr());
            $records[] = $recordFrom;

            $recordTo = new EnergyData();
            $recordTo->setTariff($distributionTariff);
            $recordTo->setBillingPeriodFrom($billingPeriodFrom);
            $recordTo->setBillingPeriodTo($billingPeriodTo);
            $recordTo->setConsumptionKwh($contract->getConsumption() / $contract->getPeriodInMonths());
            $recordTo->setCalculatedConsumptionKwh($contract->getConsumption() / $contract->getPeriodInMonths());
            $recordTo->setStateEnd($recordTo->getConsumptionKwh());
            $recordTo->setCode($contract->getOsd()->getOption());
            $recordTo->setDeviceId($contract->getGasMeterFabricNr());
            $records[] = $recordTo;

            $recordsGroupedByAreas = ["" => $records];
        } else { // ENERGY
            foreach ($energyPrices as $energyPrice) {
                $records = [];

                $divider = 1;
                if ($energyPrice['typeCode'] == TariffModel::TARIFF_ZONE_DAY || $energyPrice['typeCode'] == TariffModel::TARIFF_ZONE_PEAK) {
                    $divider = 24 / 16;
                }
                if ($energyPrice['typeCode'] == TariffModel::TARIFF_ZONE_NIGHT || $energyPrice['typeCode'] == TariffModel::TARIFF_ZONE_OFF_PEAK) {
                    $divider = 24 / 8;
                }
                $quantity = $contract->getConsumption();
                $quantity = $quantity / $contract->getPeriodInMonths() / $divider;
                $quantity = number_format($quantity, 5, '.', '');

                $recordFrom = new EnergyData();
                $recordFrom->setTariff($distributionTariff);
                $recordFrom->setArea($energyPrice['typeCode']);
                $recordFrom->setBillingPeriodFrom(null);
                $recordFrom->setBillingPeriodTo($billingPeriodFrom);
                $recordFrom->setConsumptionKwh(0);
                $recordFrom->setCalculatedConsumptionKwh(0);
                $recordFrom->setStateEnd(0);
                $recordFrom->setDeviceId($contract->getPpCounterNr());
                $records[] = $recordFrom;

                $recordTo = new EnergyData();
                $recordTo->setTariff($distributionTariff);
                $recordTo->setArea($energyPrice['typeCode']);
                $recordTo->setBillingPeriodFrom($billingPeriodFrom);
                $recordTo->setBillingPeriodTo($billingPeriodTo);
                $recordTo->setConsumptionKwh($quantity);
                $recordTo->setCalculatedConsumptionKwh($quantity);
                $recordTo->setStateEnd($recordTo->getConsumptionKwh());
                $recordTo->setDeviceId($contract->getPpCounterNr());
                $records[] = $recordTo;

                $recordsGroupedByAreas[$energyPrice['typeCode']] = $records;
            }
        }
        $billingPeriodFromTmp = clone $billingPeriodFrom;

        $data = $this->settlementModel->prepareData($records, $recordsGroupedByAreas, $records, $clientAndContract, $billingPeriodFromTmp, $billingPeriodTo, true);

        $index = 1;
        foreach ($data['summaryData'] as $item) {
            $invoiceProduct = new InvoiceProduct(new Helper());
            $invoiceProduct->setId($index);
            $invoiceProduct->setTitle($item['title']);
            $invoiceProduct->setVatPercentage($item['vatPercentage']);
            $invoiceProduct->setNetValue(number_format($item['netValue'], 2, '.', ''));
            $invoiceProduct->setPriceValue($item['priceValue']);
            $invoiceProduct->setGrossValue($invoiceProduct->getGrossValue());
            $invoiceProduct->setUnit($item['unit']);
            $invoiceProduct->setQuantity($item['consumption']);
            $invoiceProduct->setExcise(0);
            $invoiceProduct->setCustom([
                'area' => $item['area'],
                'deviceNumber' => isset($item['deviceId']) ? $item['deviceId'] : null,
            ]);
            $invoiceProducts[] = $invoiceProduct;
            $index++;
        }

        $document->setConsumptionByDeviceData($data['consumptionByDevices']);

        $invoiceProductGroup = new InvoiceProductGroup();
        $invoiceProductGroup->setId(1);
        $invoiceProductGroup->setProducts($invoiceProducts);
        $document->setData([$invoiceProductGroup]);

        $invoiceData = new InvoiceData(new Helper());
        $invoiceData->setProductGroups([$invoiceProductGroup]);

        $vatGroups = $invoiceData->getProductsGroupsSummaryGroupedByVat();
        $document->setSummaryNetValue($vatGroups['summary']['netValue']);
        $document->setSummaryVatValue($vatGroups['summary']['vatValue']);
        $document->setSummaryGrossValue($vatGroups['summary']['grossValue']);

        $document->recalculateConsumption();
        if ($document->getType() == 'ENERGY') {
            $document->setExcise($excise);
            $document->recalculateExciseValue();
        } else {
            $document->setExcise(0);
            $document->setExciseValue(0);
        }

        return $document;
    }

    /**
     * @return DocumentPackageToGenerate
     */
    private function getPackageToProcess()
    {
        /** @var DocumentPackageToGenerate $package */
        $package = $this->documentPackageToGenerateModel->getSingleRecordByStatus(DocumentPackageToGenerateModel::STATUS_IN_PROCESS);
        if (!$package) {
            dump('No packages with "to process" status.');
            die;
        }
        return $package;
    }

    /**
     * @param DocumentPackageToGenerate $package
     * @return DocumentPackageToGenerateRecord
     */
    private function getActualProcessedRecord(DocumentPackageToGenerate $package)
    {
        /** @var DocumentPackageToGenerateRecord $packageRecord */
        foreach ($package->getPackageRecords() as $packageRecord) {
            if ($packageRecord->getStatus() == DocumentPackageToGenerateRecordModel::STATUS_IN_PROCESS) {
                return $packageRecord;
            }
        }
        return null;
    }

    /**
     * @param $package
     */
    protected function resetPackageStatus(DocumentPackageToGenerate $package)
    {
        $package->setStatus(DocumentPackageToGenerateModel::STATUS_WAITING_TO_PROCESS);
        $this->em->persist($package);
        $this->em->flush($package);
    }

    private function allRecordsProcessed(DocumentPackageToGenerate $package)
    {
        $summaryCount = count($package->getPackageRecords());
        $checkedSummaryCount = $package->getCountCompleted() + $package->getCountError();
        return $summaryCount == $checkedSummaryCount;
    }

    /**
     * @param OutputInterface $output
     * @return LockHandler
     */
    private function setUpLock(OutputInterface $output)
    {
        $lock = new LockHandler('document_package_to_generate_process');
        if (!$lock->lock()) {
            $output->writeln('This command is already running in another process.');
            die;
        }
        return $lock;
    }

}