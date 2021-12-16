<?php

namespace Wecoders\EnergyBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\ContractEnergyBase;
use GCRM\CRMBundle\Service\BillingDocument\Initializer;
use GCRM\CRMBundle\Service\EasyAdminModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\LockHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Wecoders\EnergyBundle\Entity\MergeInvoicesPackage;
use Wecoders\EnergyBundle\Entity\MergeInvoicesPackageRecord;
use Wecoders\EnergyBundle\Entity\PriceList;
use Wecoders\EnergyBundle\Entity\PriceListData;
use Wecoders\EnergyBundle\Entity\PriceListDataAndTariff;
use Wecoders\EnergyBundle\Entity\PriceListDataAndYearWithPrice;
use Wecoders\EnergyBundle\Entity\SettlementPackage;
use Wecoders\EnergyBundle\Entity\SettlementPackageRecord;
use Wecoders\EnergyBundle\Entity\Tariff;
use Wecoders\EnergyBundle\Service\InvoiceModel;
use Wecoders\EnergyBundle\Service\MergeInvoicesPackageModel;
use Wecoders\EnergyBundle\Service\MergeInvoicesPackageRecordModel;
use Wecoders\EnergyBundle\Service\SettlementModel;
use Wecoders\EnergyBundle\Service\SettlementPackageModel;
use Wecoders\EnergyBundle\Service\SettlementPackageRecordModel;

class MergeInvoicesPackageStatusProcess extends Command
{
    /* @var EntityManager */
    private $em;

    private $container;

    private $settlementPackageModel;

    private $invoiceModel;

    private $initializer;

    private $easyAdminModel;

    private $packageModel;

    private $packageRecordModel;

    public function __construct(
        ContainerInterface $container,
        EntityManager $em,
        InvoiceModel $invoiceModel,
        EasyAdminModel $easyAdminModel,
        MergeInvoicesPackageModel $packageModel,
        MergeInvoicesPackageRecordModel $packageRecordModel
    )
    {
        $this->em = $em;
        $this->container = $container;
        $this->invoiceModel = $invoiceModel;
        $this->easyAdminModel = $easyAdminModel;
        $this->packageModel = $packageModel;
        $this->packageRecordModel = $packageRecordModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('wecodersenergybundle:merge-invoices-package-status-process')
            ->setDescription('Process merge invoices package.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // set up lock, so command can be used only in single process to avoid duplicates
        $lock = new LockHandler('merge_invoices_package_status_process');
        if (!$lock->lock()) {
            $output->writeln('This command is already running in another process.');
            return 0;
        }

        $em = $this->em;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $kernelRootDir = $this->container->get('kernel')->getRootDir();



        /** @var MergeInvoicesPackage $package */
        $package = $this->packageModel->getSingleRecordByStatus(MergeInvoicesPackageModel::STATUS_IN_PROCESS);
        if (!$package) {
            dump('No packages with "to process" status.');
            die;
        }

        $packageRecords = $this->packageRecordModel->getRecordsByPackage($package);


        $index = 1;
        $em->getConnection()->beginTransaction();
        try {

            $invoices = [];

            /** @var MergeInvoicesPackageRecord $packageRecord */
            foreach ($packageRecords as $packageRecord) {
                $invoice = $this->manageInvoice($packageRecord);
                if (!$invoice) {
                    throw new NotFoundHttpException(sprintf('Rekord nie ma przypisanej faktury (#%s)', $packageRecord->getId()));
                }

                $invoices[] = $invoice;
            }







            die;

            $em->persist();
            $em->flush();

            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollBack();

            /** @var SettlementPackageRecord $reFetchedPackage*/
            $reFetchedPackage = $this->packageModel->getRecord($package->getId());
            $reFetchedPackage->setErrorMessage($e->getMessage() . ' - on line: ' . $e->getLine());
            $reFetchedPackage->setStatus(MergeInvoicesPackageModel::STATUS_PROCESS_ERROR);
            $em->persist($reFetchedPackage);
            $em->flush();
        }




        $em->clear();
        $em->getConnection()->close();
        dump('Success');
        // release lock, so command can be used again
        $lock->release();


        // here can command be safely shoot again if more records exist
        shell_exec('php ' . $kernelRootDir . '/../bin/console wecodersenergybundle:settlement-package-status-process');
    }

    private function manageInvoice(MergeInvoicesPackageRecord $packageRecord)
    {
        $invoice = $packageRecord->getInvoiceSettlement();
        if (!$invoice) {
            $invoice = $packageRecord->getInvoiceEstimatedSettlement();
        }

        return $invoice;
    }

}