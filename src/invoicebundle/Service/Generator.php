<?php

namespace Wecoders\InvoiceBundle\Service;

class Generator
{
    public function generate(InvoiceData $invoiceDataObject, $pagesHtml = [], FooterData $footerData)
    {
        /** @var InvoiceTCPDF $pdf */
        $pdf = $this->getPdfSetupObject($footerData);

        foreach ($pagesHtml as $pageHtml) {
            $pdf->AddPage();
            $pdf->writeHTML($pageHtml, true, false, true, false, '');
        }

        $pdf->Output($invoiceDataObject->getDirectoryOutput() . '/' . $invoiceDataObject->getFilename() . '.pdf', 'F');
    }

    private function getPdfSetupObject(FooterData $footerData)
    {
        $pdf = new InvoiceTCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->setFooterHtml($footerData);

        $pdf->SetCreator(PDF_CREATOR);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(7, 7, 7);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetFont('dejavusans', '', 7);

        return $pdf;
    }

}