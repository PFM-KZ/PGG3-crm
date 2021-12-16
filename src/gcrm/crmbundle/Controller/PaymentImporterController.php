<?php

namespace GCRM\CRMBundle\Controller;

use GCRM\CRMBundle\Event\PaymentsUploadedEvent;
use GCRM\CRMBundle\Form\ImporterPaymentsType;
use GCRM\CRMBundle\Service\PaymentImporter\Exception\EmptyFileException;
use GCRM\CRMBundle\Service\PaymentImporter\Exception\FileAlreadyExistException;
use GCRM\CRMBundle\Service\PaymentImporter\Exception\InvalidFilenameException;
use GCRM\CRMBundle\Service\PaymentImporter\PaymentImporterFactory;
use GCRM\CRMBundle\Service\PaymentImporter\PaymentImporterInterface;
use GCRM\CRMBundle\Service\PaymentImporterModel;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class PaymentImporterController extends Controller
{
    /**
     * @Route("/importer-payments", name="importerPayments")
     */
    public function importerPaymentsAction(Request $request, PaymentImporterFactory $paymentImporterFactory)
    {
        $form = $this->createForm(ImporterPaymentsType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bankType = $form->get('bank')->getData();

            if (PaymentImporterModel::checkIfValidFileBankType($bankType)) {
                /** @var UploadedFile $file */
                $file = $form->get('file')->getData();

                /** @var PaymentImporterInterface $importer */
                $importer = $paymentImporterFactory->create($bankType);
                $importer->init($file);

                $error = null;
                try {
                    $payments = $importer->execute();
                } catch (FileAlreadyExistException $e) {
                    $error = $e->getMessage();
                } catch (EmptyFileException $e) {
                    $error = $e->getMessage();
                } catch (InvalidFilenameException $e) {
                    $error = $e->getMessage();
                }

                if ($error) {
                    $this->addFlash('error', $error);
                    return $this->redirectToRoute('importerPayments');
                }

                // Dispatching the event
                $paymentsUploadedEvent = new PaymentsUploadedEvent($payments);
                $this->get('event_dispatcher')->dispatch('payments.uploaded', $paymentsUploadedEvent);

                $this->addFlash('success', 'Sukces.');
                return $this->redirectToRoute('importerPayments');
            }
        }

        // payment files
        $optionArray = PaymentImporterModel::getOptionArray();
        $uploadedFilesGroupedByBank = [];
        foreach ($optionArray as $id => $option) {
            /** @var PaymentImporterInterface $importer */
            $importer = $paymentImporterFactory->create($id);
            $importer->initDir();

            $values = array_values($importer->getFiles());
            if (isset($values[0])) {
                $values = $values[0];
            }
            $uploadedFilesGroupedByBank[$option] = $values;
        }

        return $this->render('@GCRMCRM/Default/send-file.html.twig', [
            'form' => $form->createView(),
            'uploadedFilesGroupedByBank' => $uploadedFilesGroupedByBank
        ]);
    }
}
