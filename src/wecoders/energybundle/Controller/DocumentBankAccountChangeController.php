<?php

namespace Wecoders\EnergyBundle\Controller;

use AppBundle\Service\UploadedFileHelper;
use Doctrine\ORM\EntityManagerInterface;
use GCRM\CRMBundle\Service\EasyAdminModel;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wecoders\EnergyBundle\Entity\DocumentBankAccountChange;
use Wecoders\EnergyBundle\Service\DocumentBankAccountChangeModel;
use Wecoders\EnergyBundle\Service\SpreadsheetReader;

class DocumentBankAccountChangeController extends Controller
{
    /**
     * @Route("/document-bank-account-change", name="documentBankAccountChange")
     */
    public function indexAction(
        Request $request,
        EntityManagerInterface $em,
        SpreadsheetReader $spreadsheetReader,
        UploadedFileHelper $uploadedFileHelper
    )
    {
        $fullPathToFile = $uploadedFileHelper->createTmpFile($request->files->get('file_upload')['file'], 'tmp-document-bank-account-change-file');

        $rows = $spreadsheetReader->fetchRows('Xlsx', $fullPathToFile, 2, 'A');

        try {
            $this->validate($rows);
        } catch (\Exception $e) {
            return new Response($e->getMessage());
        }

        foreach ($rows as $row) {
            $documentBankAccountChange = new DocumentBankAccountChange();
            $documentBankAccountChange->setBadgeId($row[0]);

            $em->persist($documentBankAccountChange);
            $em->flush($documentBankAccountChange);
        }
        $this->addFlash('success', 'Rekordy zostały wgrane');

        return $this->redirectToRoute('easyadmin', [
            'action' => 'list',
            'entity' => 'DocumentBankAccountChange',
        ]);
    }

    /**
     * @Route("/document-bank-account-change-remove-unlinked", name="removeUnlinkedDocumentsBankAccountChange")
     */
    public function removeUnlinkedDocumentsBankAccountChangeAction(
        EntityManagerInterface $em,
        DocumentBankAccountChangeModel $documentBankAccountChangeModel
    )
    {
        $records = $documentBankAccountChangeModel->getGeneratedNotAssignedRecords();
        if (!$records) {
            $this->addFlash('notice', 'Brak rekordów do wyczyszczenia.');
            return $this->redirectToRoute('easyadmin', [
                'action' => 'list',
                'entity' => 'DocumentBankAccountChange',
            ]);
        }

        /** @var DocumentBankAccountChange $record */
        foreach ($records as $record) {
            if ($record->getDocumentNumber() || !$record->getFilePath()) {
                continue;
            }

            if (file_exists($record->getFilePath())) {
                unlink($record->getFilePath());
            }

            $record->setFilePath(null);
            $em->persist($record);
            $em->flush($record);
        }

        $this->addFlash('success', 'Wykonano.');
        return $this->redirectToRoute('easyadmin', [
            'action' => 'list',
            'entity' => 'DocumentBankAccountChange',
        ]);
    }

    private function validate(&$rows)
    {
        $this->validateNotEmpty($rows);
        $this->validateUnique($rows);
    }

    private function validateNotEmpty(&$rows)
    {
        if (!is_array($rows) || !count($rows)) {
            throw new \Exception('Plik nie został wgrany ponieważ jest pusty');
        }
    }

    private function validateUnique(&$rows)
    {
        $accountNumberIdentifiers = [];

        foreach ($rows as $row) {
            if (!in_array($row[0], $accountNumberIdentifiers)) {
                $accountNumberIdentifiers[] = $row[0];
            }
        }

        if (count($accountNumberIdentifiers) != count($rows)) {
            throw new \Exception('Plik nie został wgrany ponieważ zawiera powielające się wartości');
        }
    }

    /**
     * @Route("/display-document-bank-account-change", name="displayDocumentBankAccountChange")
     */
    public function displayDocumentBankAccountChangeAction(
        Request $request,
        DocumentBankAccountChangeModel $documentBankAccountChangeModel
    )
    {
        $id = $request->query->get('id');

        /** @var DocumentBankAccountChange $documentBankAccountChange */
        $documentBankAccountChange = $documentBankAccountChangeModel->getRecord($id);

        $fullPath = $documentBankAccountChange->getFilePath();
        $fullInvoicePathWithExtension = $fullPath . '.pdf';

        if (file_exists($fullInvoicePathWithExtension)) {
            $fullPath = $fullInvoicePathWithExtension;
        }

        header('Content-type: application/pdf');
        header('Content-Disposition: inline; filename="ZNR-' . $documentBankAccountChange->getBadgeId() . '.pdf"');
        echo readfile($fullPath);
        die;
    }

}
