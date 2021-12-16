<?php

namespace AppBundle\Service;

use setasign\Fpdi\Fpdi;

class PdfHelper
{
    public function mergePdfFiles($files, $outputFilePath)
    {
        $pdf = new Fpdi();

        // iterate through the files
        foreach ($files as $file) {
            // get the page count
            $pageCount = $pdf->setSourceFile($file);
            // iterate through all pages
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                // import a page
                $templateId = $pdf->importPage($pageNo);
                // get the size of the imported page
                $size = $pdf->getTemplateSize($templateId);

                // create a page (landscape or portrait depending on the imported page size)
                if ($size['width'] > $size['height']) {
                    $pdf->AddPage('L', array($size['width'], $size['height']));
                } else {
                    $pdf->AddPage('P', array($size['width'], $size['height']));
                }

                // use the imported page
                $pdf->useTemplate($templateId);
            }
        }

        $pdf->Output($outputFilePath, 'F');
    }
}