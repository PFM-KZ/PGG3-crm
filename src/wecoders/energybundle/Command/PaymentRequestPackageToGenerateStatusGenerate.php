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
use Wecoders\EnergyBundle\Entity\PaymentRequest;
use Wecoders\EnergyBundle\Entity\PaymentRequestPackageToGenerate;
use Wecoders\EnergyBundle\Entity\PriceList;
use Wecoders\EnergyBundle\Entity\PriceListData;
use Wecoders\EnergyBundle\Entity\PriceListDataAndTariff;
use Wecoders\EnergyBundle\Entity\PriceListDataAndYearWithPrice;
use Wecoders\EnergyBundle\Entity\Tariff;
use Wecoders\EnergyBundle\Service\PackageToGenerateModel;
use Wecoders\EnergyBundle\Service\PaymentRequestModel;
use Wecoders\EnergyBundle\Service\PaymentRequestPackageToGenerateModel;
use Wecoders\InvoiceBundle\Entity\InvoiceTemplate;
use Wecoders\InvoiceBundle\Service\InvoiceModel;
use Wecoders\InvoiceBundle\Service\InvoiceTemplateModel;

class PaymentRequestPackageToGenerateStatusGenerate extends Command
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

    private $paymentRequestModel;

    public function __construct(ContainerInterface $container, EntityManager $em, PaymentRequestPackageToGenerateModel $packageToGenerateModel, InvoiceModel $invoiceModel, \Wecoders\EnergyBundle\Service\InvoiceModel $energyBundleInvoiceModel, InvoiceTemplateModel $invoiceTemplateModel, EasyAdminModel $easyAdminModel, ContractModel $contractModel, PaymentRequestModel $paymentRequestModel)
    {
        $this->em = $em;
        $this->container = $container;
        $this->packageToGenerateModel = $packageToGenerateModel;
        $this->invoiceModel = $invoiceModel;
        $this->invoiceTemplateModel = $invoiceTemplateModel;
        $this->easyAdminModel = $easyAdminModel;
        $this->energyBundleInvoiceModel = $energyBundleInvoiceModel;
        $this->contractModel = $contractModel;
        $this->paymentRequestModel = $paymentRequestModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('wecodersenergybundle:payment-request-package-to-generate-status-generate')
            ->setDescription('Generate documents from package.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // set up lock, so command can be used only in single process to avoid duplicates
        $lock = new LockHandler('payment_request_package_to_generate_status_generate');
        if (!$lock->lock()) {
            $output->writeln('This command is already running in another process.');
            return 0;
        }

        $em = $this->em;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $kernelRootDir = $this->container->get('kernel')->getRootDir();



        /** @var PaymentRequestPackageToGenerate $packageToGenerate */
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
            // all objects were checked, so all records documents are ready to be generated
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
            /** @var PaymentRequest $document */
            $document = $em->getRepository('WecodersEnergyBundle:PaymentRequest')->find($documentToCheckId);
            if (!$document) {
                die('Dokument nie istnieje');
            }

            /** @var ContractEnergyBase $contract */
            $contract = $this->contractModel->getContractByNumber($document->getContractNumber(), [
                'GCRMCRMBundle:ClientAndContractEnergy' => 'GCRMCRMBundle:ContractEnergy',
                'GCRMCRMBundle:ClientAndContractGas' => 'GCRMCRMBundle:ContractGas',
            ]);
            if (!$contract) {
                die('Umowa z podanym numerem na fakturze nie istnieje.');
            }

            // GENERATE FILES

            /** @var InvoiceTemplate $documentTemplate */
            $documentTemplate = $document->getDocumentTemplate();
            if (!$documentTemplate || !$documentTemplate->getFilePath() || !file_exists($this->invoiceTemplateModel->getTemplateAbsolutePath($documentTemplate->getFilePath()))) {
                die('Szablon dokumentu nie istnieje');
            }
            $templateAbsolutePath = $this->invoiceTemplateModel->getTemplateAbsolutePath($documentTemplate->getFilePath());
            $documentPath = $this->paymentRequestModel->getDocumentPath($document, $this->easyAdminModel->getEntityDirectoryByEntityName('PaymentRequest'));

            $brand = $contract->getBrand();
            $logoAbsolutePath = $this->paymentRequestModel->getLogoAbsolutePath($brand);



            $fileGenerated = false;
            for ($i = 0; $i < 3; $i++) {
                $this->paymentRequestModel->generatePaymentRequestDocument($document, $documentPath, $templateAbsolutePath, $logoAbsolutePath, $contract->getType());
                dump('Generate file attempt.');
                if (file_exists($documentPath . '.pdf')) {
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
        shell_exec('php ' . $kernelRootDir . '/../bin/console wecodersenergybundle:payment-request-package-to-generate-status-generate');
    }

}