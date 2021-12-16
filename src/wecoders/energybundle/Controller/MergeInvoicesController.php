<?php

namespace Wecoders\EnergyBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use GCRM\CRMBundle\Entity\Company;
use GCRM\CRMBundle\Service\AccountNumberIdentifierModel;
use GCRM\CRMBundle\Service\AccountNumberModel;
use GCRM\CRMBundle\Service\CompanyModel;
use GCRM\CRMBundle\Service\EasyAdminModel;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Wecoders\EnergyBundle\Entity\ICollectiveMarkable;
use Wecoders\EnergyBundle\Entity\InvoiceEstimatedSettlement;
use Wecoders\EnergyBundle\Entity\InvoiceInterface;
use Wecoders\EnergyBundle\Entity\InvoiceSettlement;
use Wecoders\EnergyBundle\Entity\MergeInvoicesPackage;
use Wecoders\EnergyBundle\Entity\MergeInvoicesPackageRecord;
use Wecoders\EnergyBundle\Form\MergeInvoicesFileType;
use Wecoders\EnergyBundle\Service\ContractAccessor;
use Wecoders\EnergyBundle\Service\EnveloModel;
use Wecoders\EnergyBundle\Service\InvoiceModel;
use Wecoders\EnergyBundle\Service\MergeInvoicesPackageModel;
use Wecoders\EnergyBundle\Service\MergeInvoicesPackageRecordModel;
use Wecoders\EnergyBundle\Service\SpreadsheetReader;

class MergeInvoicesController extends Controller
{
    /** @var ContractAccessor $contractAccessor */
    private $contractAccessor;

    /** @var  InvoiceModel */
    private $invoiceModel;

    /**
     * @Route("/merge-invoices-panel", name="mergeInvoicesPanel")
     */
    public function mergeInvoicesPanelAction(Request $request, EntityManager $em, ContractAccessor $contractAccessor, SpreadsheetReader $spreadsheetReader, InvoiceModel $invoiceModel)
    {
        $this->contractAccessor = $contractAccessor;
        $this->invoiceModel = $invoiceModel;

        $form = $this->createForm(MergeInvoicesFileType::class);
        $form->handleRequest($request);

        // INPUT FILE RECORDS
        if ($form->isSubmitted() && $form->isValid()) {
            $fullPathToFile = $this->setTmpInputFile($form->get('file')->getData());
            $createdDate = $form->get('createdDate')->getData();

            if (file_exists($fullPathToFile)) {
                $rows = $spreadsheetReader->fetchRows('Xlsx', $fullPathToFile, 2, 'B');

                try {
                    $this->validate($rows);
                } catch (\Exception $e) {
                    return new Response($e->getMessage());
                }

                $em->getConnection()->beginTransaction();
                try {
                    // create package
                    $package = new MergeInvoicesPackage();
                    $package->setAddedBy($this->getUser());
                    if ($createdDate) {
                        $package->setCreatedDate($createdDate);
                    }
                    $em->persist($package);
                    $em->flush($package);

                    // create package records
                    foreach ($rows as $row) {
                        $pp = $row[0];
                        $invoiceNumber = $row[1];
                        $invoiceFromDb = $this->manageInvoice($invoiceNumber);

                        $packageRecord = new MergeInvoicesPackageRecord();
                        $packageRecord->setPackage($package);
                        $packageRecord->setPp($pp);

                        if ($invoiceFromDb instanceof InvoiceEstimatedSettlement) {
                            $packageRecord->setInvoiceEstimatedSettlement($invoiceFromDb);
                        } elseif ($invoiceFromDb instanceof InvoiceSettlement) {
                            $packageRecord->setInvoiceSettlement($invoiceFromDb);
                        } else {
                            throw new \Exception('Błędny typ faktury');
                        }

                        $em->persist($packageRecord);
                        $em->flush();
                    }

                    $em->getConnection()->commit();
                    $this->addFlash('success', 'Wygenerowano rekordy');
                    unlink($fullPathToFile);
                } catch (\Exception $e) {
                    $this->addFlash('error', sprintf('Wystąpił błąd: %s', $e->getMessage()));
                    $em->getConnection()->rollBack();
                }

                return $this->redirectToRoute('mergeInvoicesPanel');
            }
        }

        $packages = $em->getRepository('WecodersEnergyBundle:MergeInvoicesPackage')->findAll();

        return $this->render('@WecodersEnergyBundle/mergeInvoices/panel.html.twig', [
            'formInputFile' => $form->createView(),
            'packages' => $packages,
        ]);
    }

    /**
     * @Route("/merge-invoices-panel/package/{id}", name="mergeInvoicesPanelPackage")
     */
    public function mergeInvoicesPanelPackageAction(Request $request, EntityManager $em, EasyAdminModel $easyAdminModel, \Wecoders\InvoiceBundle\Service\InvoiceModel $invoiceBundleInvoiceModel, MergeInvoicesPackageModel $mergeInvoicesPackageModel, MergeInvoicesPackageRecordModel $mergeInvoicesPackageRecordModel, $id)
    {
        /** @var MergeInvoicesPackage $package */
        $package = $mergeInvoicesPackageModel->getRecord($id);
        if (!$package) {
            throw new NotFoundHttpException();
        }

        $packageRecords = $mergeInvoicesPackageRecordModel->getRecordsByPackage($package);

        return $this->render('@WecodersEnergyBundle/mergeInvoices/package.html.twig', [
            'package' => $package,
            'records' => $packageRecords,
        ]);
    }

    private function validate(&$rows)
    {
        $this->validateContractsForPpExist($rows);
        $this->validateInvoiceExist($rows);
        $this->validateSamePpExistOnInvoice($rows);
        $this->validateSameBillingPeriodsOnInvoices($rows);
        $this->validateSameClientDataOnInvoices($rows);
    }

    private function validateContractsForPpExist(&$rows)
    {
        $notFound = [];
        foreach ($rows as $row) {
            $pp = $row[0];
            $clientAndContract = $this->contractAccessor->accessClientAndContractBy('ppCode', $pp, 'contractAndPpCode');
            if (!$clientAndContract) {
                $notFound[] = $pp;
            }
        }

        if (count($notFound)) {
            throw new NotFoundHttpException('Plik nie został wgrany ponieważ nie znaleziono klientów/umów dla danych kodów PP (popraw dane z pliku i wgraj jeszcze raz):<br>' . implode('<br>', $notFound));
        }
    }

    private function validateInvoiceExist(&$rows)
    {
        $invalidRecords = [];
        foreach ($rows as $row) {
            $invoiceNumber = $row[1];

            $invoiceFromDb = $invoiceFromDb = $this->manageInvoice($invoiceNumber);

            if (!$invoiceFromDb) {
                $invalidRecords[] = $invoiceNumber;
            }
        }

        if (count($invalidRecords)) {
            throw new NotFoundHttpException('Plik nie został wgrany ponieważ nie odnaleziono faktur (popraw dane z pliku lub na dokumentach i wgraj jeszcze raz):<br>' . implode('<br>', $invalidRecords));
        }
    }

    private function validateSamePpExistOnInvoice(&$rows)
    {
        $invalidRecords = [];
        foreach ($rows as $row) {
            $pp = $row[0];
            $invoiceNumber = $row[1];

            /** @var InvoiceInterface $invoiceFromDb */
            $invoiceFromDb = $this->manageInvoice($invoiceNumber);

            if ($invoiceFromDb && $invoiceFromDb->getPpEnergy() != $pp) {
                $invalidRecords[] = $invoiceNumber;
            }
        }

        if (count($invalidRecords)) {
            throw new NotFoundHttpException('Plik nie został wgrany ponieważ kody PP z pliku nie odpowiadają kodom PP na fakturach (popraw dane z pliku lub na dokumentach i wgraj jeszcze raz):<br>' . implode('<br>', $invalidRecords));
        }
    }

    private function validateSameBillingPeriodsOnInvoices(&$rows)
    {
        $invalidRecords = [];
        $billingPeriodFrom = null;
        $billingPeriodTo = null;

        $index = 1;
        foreach ($rows as $row) {
            $invoiceNumber = $row[1];

            /** @var InvoiceInterface $invoiceFromDb */
            $invoiceFromDb = $this->manageInvoice($invoiceNumber);
            // check only on first iteration
            if ($index == 1) {
                $billingPeriodFrom = $invoiceFromDb->getBillingPeriodFrom();
                $billingPeriodTo = $invoiceFromDb->getBillingPeriodTo();
            }

            if (
                $invoiceFromDb->getBillingPeriodFrom() != $billingPeriodFrom ||
                $invoiceFromDb->getBillingPeriodTo() != $billingPeriodTo
            ) {
                $invalidRecords[] = $invoiceNumber;
            }

            $index++;
        }
        if (count($invalidRecords)) {
            throw new NotFoundHttpException('Plik nie został wgrany ponieważ okresy rozliczeniowe faktur są różne: <br>' . implode('<br>', $invalidRecords));
        }
    }

    private function validateSameClientDataOnInvoices(&$rows)
    {
        $invalidRecords = [];

        $clientFullName = null;
        $clientNip = null;
        $clientZipCode = null;
        $clientCity = null;
        $clientStreet = null;
        $clientHouseNr = null;
        $clientApartmentNumber = null;

        $recipientCompanyName = null;
        $recipientNip = null;
        $recipientZipCode = null;
        $recipientCity = null;
        $recipientStreet = null;
        $recipientHouseNr = null;
        $recipientApartmentNumber = null;

        $payerCompanyName = null;
        $payerNip = null;
        $payerZipCode = null;
        $payerCity = null;
        $payerStreet = null;
        $payerHouseNr = null;
        $payerApartmentNumber = null;

        $index = 1;
        foreach ($rows as $row) {
            $invoiceNumber = $row[1];

            /** @var InvoiceInterface $invoiceFromDb */
            $invoiceFromDb = $this->manageInvoice($invoiceNumber);
            // check only on first iteration
            if ($index == 1) {
                $clientFullName = $invoiceFromDb->getClientFullName();
                $clientNip = $invoiceFromDb->getClientNip();
                $clientZipCode = $invoiceFromDb->getClientZipCode();
                $clientCity = $invoiceFromDb->getClientCity();
                $clientStreet = $invoiceFromDb->getClientStreet();
                $clientHouseNr = $invoiceFromDb->getClientHouseNr();
                $clientApartmentNumber = $invoiceFromDb->getClientApartmentNr();

                $recipientCompanyName = $invoiceFromDb->getRecipientCompanyName();
                $recipientNip = $invoiceFromDb->getRecipientNip();
                $recipientZipCode = $invoiceFromDb->getRecipientZipCode();
                $recipientCity = $invoiceFromDb->getRecipientCity();
                $recipientStreet = $invoiceFromDb->getRecipientStreet();
                $recipientHouseNr = $invoiceFromDb->getRecipientHouseNr();
                $recipientApartmentNumber = $invoiceFromDb->getRecipientApartmentNr();

                $payerCompanyName = $invoiceFromDb->getPayerCompanyName();
                $payerNip = $invoiceFromDb->getPayerNip();
                $payerZipCode = $invoiceFromDb->getPayerZipCode();
                $payerCity = $invoiceFromDb->getPayerCity();
                $payerStreet = $invoiceFromDb->getPayerStreet();
                $payerHouseNr = $invoiceFromDb->getPayerHouseNr();
                $payerApartmentNumber = $invoiceFromDb->getPayerApartmentNr();
            }

            if (
                $clientFullName != $invoiceFromDb->getClientFullName() ||
                $clientNip != $invoiceFromDb->getClientNip() ||
                $clientZipCode != $invoiceFromDb->getClientZipCode() ||
                $clientCity != $invoiceFromDb->getClientCity() ||
                $clientStreet != $invoiceFromDb->getClientStreet() ||
                $clientHouseNr != $invoiceFromDb->getClientHouseNr() ||
                $clientApartmentNumber != $invoiceFromDb->getClientApartmentNr() ||

                $recipientCompanyName != $invoiceFromDb->getRecipientCompanyName() ||
                $recipientNip != $invoiceFromDb->getRecipientNip() ||
                $recipientZipCode != $invoiceFromDb->getRecipientZipCode() ||
                $recipientCity != $invoiceFromDb->getRecipientCity() ||
                $recipientStreet != $invoiceFromDb->getRecipientStreet() ||
                $recipientHouseNr != $invoiceFromDb->getRecipientHouseNr() ||
                $recipientApartmentNumber != $invoiceFromDb->getRecipientApartmentNr() ||

                $payerCompanyName != $invoiceFromDb->getPayerCompanyName() ||
                $payerNip != $invoiceFromDb->getPayerNip() ||
                $payerZipCode != $invoiceFromDb->getPayerZipCode() ||
                $payerCity != $invoiceFromDb->getPayerCity() ||
                $payerStreet != $invoiceFromDb->getPayerStreet() ||
                $payerHouseNr != $invoiceFromDb->getPayerHouseNr() ||
                $payerApartmentNumber != $invoiceFromDb->getPayerApartmentNr()
            ) {
                $invalidRecords[] = $invoiceNumber;
            }

            $index++;
        }

        if (count($invalidRecords)) {
            throw new NotFoundHttpException('Dane klienta płatnik / odbiorca / nabywca są różne: <br>' . implode('<br>', $invalidRecords));
        }
    }

    public function manageInvoice($invoiceNumber)
    {
        /** @var InvoiceInterface $invoiceFromDb */
        $invoiceFromDb = $this->invoiceModel->getInvoiceByEntity(InvoiceSettlement::class, $invoiceNumber);
        if (!$invoiceFromDb) {
            $invoiceFromDb = $this->invoiceModel->getInvoiceByEntity(InvoiceEstimatedSettlement::class, $invoiceNumber);
        }

        return $invoiceFromDb;
    }

    private function setTmpInputFile(UploadedFile $file)
    {
        $kernelRootDir = $this->get('kernel')->getRootDir();
        $tmpFilename = 'tmp-merge-invoices-from-file';
        $absoluteUploadDirectoryPath = $kernelRootDir . '/../var/data';
        $fullPathToFile = $kernelRootDir . '/../var/data/' . $tmpFilename;
        if (file_exists($fullPathToFile)) {
            unlink($fullPathToFile);
        }

        $file->move($absoluteUploadDirectoryPath, $tmpFilename);
        return $fullPathToFile;
    }

    /**
     * @Route("/mergeInvoicesPackageToGenerateListPostAction", name="mergeInvoicesPackageToGenerateListPostAction")
     */
    public function mergeInvoicesPackageToGenerateListPostAction(
        Request $request,
        EntityManager $em,
        MergeInvoicesPackageModel $mergeInvoicesPackageModel,
        InvoiceModel $invoiceModel,
        EasyAdminModel $easyAdminModel,
        \Wecoders\InvoiceBundle\Service\InvoiceModel $invoiceBundleInvoiceModel,
        EnveloModel $enveloModel
    )
    {
        $this->actionProcess($request, $mergeInvoicesPackageModel);

        $this->actionRollback(
            $request,
            $em,
            $mergeInvoicesPackageModel,
            $invoiceModel,
            $easyAdminModel,
            $invoiceBundleInvoiceModel
        );

        $this->actionGenerateEnvelo($request, $mergeInvoicesPackageModel, $enveloModel);

        return $this->redirectToRoute('mergeInvoicesPanel');
    }

    private function actionProcess(Request $request, MergeInvoicesPackageModel $mergeInvoicesPackageModel)
    {
        $id = $request->request->get('processAction');
        if ($id) {
            /** @var MergeInvoicesPackage $package */
            $package = $mergeInvoicesPackageModel->getRecord($id);
            if ($package->getInvoice()) {
                $this->addFlash('notice', 'Paczka ma już wygenerowaną fakturę.');
            } else {
                try {
                    $mergeInvoicesPackageModel->process($package);
                    $this->addFlash('success', 'Wygenerowano fakturę');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Wystąpił błąd: ' . $e->getMessage());
                }
            }

            return $this->redirectToRoute('mergeInvoicesPanel');
        }
    }

    private function actionRollback(
        Request $request,
        EntityManager $em,
        MergeInvoicesPackageModel $mergeInvoicesPackageModel,
        InvoiceModel $invoiceModel,
        EasyAdminModel $easyAdminModel,
        \Wecoders\InvoiceBundle\Service\InvoiceModel $invoiceBundleInvoiceModel
    )
    {
        $kernelRootDir = $this->get('kernel')->getRootDir();

        $selectedRows = $request->get('selectedRows');
        if ($selectedRows) {
            $multiRollBack = $request->request->get('multiRollBack');

            if ($multiRollBack) {
                try {
                    $em->getConnection()->beginTransaction();

                    foreach ($selectedRows as $rowId) {
                        /** @var MergeInvoicesPackage $package */
                        $package = $mergeInvoicesPackageModel->getRecord($rowId);

                        if ($package) {
                            $packageRecords = $package->getPackageRecords();

                            if ($packageRecords) {
                                /** @var MergeInvoicesPackageRecord $packageRecord */
                                foreach ($packageRecords as $packageRecord) {
                                    /** @var ICollectiveMarkable $invoice */
                                    $invoice = $packageRecord->getInvoice();
                                    if (!$invoice) {
                                        continue;
                                    }
                                    $invoice->setIsInInvoiceCollective(false);
                                    $em->persist($invoice);
                                    $em->remove($packageRecord);
                                }
                            }

                            // unlink documents
                            if ($package->getInvoice()) {
                                $directoryRelative = $easyAdminModel->getEntityDirectoryRelativeByEntityName('InvoiceCollective');
                                $invoicePath = $invoiceBundleInvoiceModel->fullInvoicePath($kernelRootDir, $package->getInvoice(), $directoryRelative);
                                if (file_exists($invoicePath . '.pdf')) {
                                    unlink($invoicePath . '.pdf');
                                }
                                if (file_exists($invoicePath . '.docx')) {
                                    unlink($invoicePath . '.docx');
                                }
                            }

                            $package = $mergeInvoicesPackageModel->getRecord($package->getId());
                            $em->remove($package);
                            $em->flush();
                        }
                    }

                    $em->getConnection()->commit();
                    $this->addFlash('success', 'Usunięto');
                } catch (\Exception $e) {
                    $em->getConnection()->rollBack();
                    $this->addFlash('success', 'Wystąpił błąd: ' . $e->getMessage());
                }
            }
        }
    }

    private function actionGenerateEnvelo(
        Request $request,
        MergeInvoicesPackageModel $mergeInvoicesPackageModel,
        EnveloModel $enveloModel
    )
    {
        $generateEnveloAction = $request->request->get('generateEnveloAction');
        if ($generateEnveloAction) {
            /** @var MergeInvoicesPackage $package */
            $package = $mergeInvoicesPackageModel->getRecord($generateEnveloAction);
            if ($package && $package->getInvoice()) {
                $enveloModel->generateForInvoiceCollective($package->getInvoice());
            }
        }
    }
}
