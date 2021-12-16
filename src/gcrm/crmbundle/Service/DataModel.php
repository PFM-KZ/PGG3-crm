<?php

namespace GCRM\CRMBundle\Service;

use stringEncode\Exception;

class DataModel
{
    private $pdf;

    private $filename;

    private $summaryRowNumber;

    private $summaryServicesNetRowNumber;

    private $connectionsRowNumber;

    private $productsFirstThTableRowNumber;

    private $productsLastRowNumber;

    private $notices = [];

    private $errors = [];

    private $hydrateDataStructure = [
        'companyNip' => '',

        'invoiceNr' => '',
        'dateOfInvoice' => '',
        'dateOfPayment' => '',
        'forTimeFrom' => '', // za okres od
        'forTimeTo' => '', // za okres do

        'clientNip' => '',
        'clientNr' => '',
        'clientFullName' => '',
        'clientAddress' => '',
        'clientZipcode' => '',
        'clientCity' => '',

        'connectionsNr' => '',
        'connectionsTime' => '',

        'summaryServicesNetValueNet' => '',
        'summaryServicesNetVat' => '',
        'summaryServicesNetValueVat' => '',
        'summaryServicesNetGrossValue' => '',

        'summaryNetValue' => '',
        'summaryVatValue' => '',
        'summaryGrossValue' => ''
    ];

    private $hydrateProductStructure = [
        'title' => '',
        'netValue' => '',
        'VatPercentage' => '',
        'VatValue' => '',
        'grossValue' => ''
    ];

    public function getFilename()
    {
        return $this->filename;
    }

    public function getNotices()
    {
        return $this->notices;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function addError($text)
    {
        $addText = 'Error: ' . $text . '
Filename: ' . $this->filename . '

';

        $this->errors[] = $addText;
    }

    public function addNotice($text)
    {
        $addText = 'Notice: ' . $text . '
Filename: ' . $this->filename . '

';

        $this->notices[] = $addText;
    }

    public function getHydrateDataStructure()
    {
        return $this->hydrateDataStructure;
    }

    public function getHydrateProductStructure()
    {
        return $this->hydrateProductStructure;
    }

    public function getPdf()
    {
        return $this->getPdf();
    }

    /**
     * @param \Gufy\PdfToHtml\Pdf $pdf
     */
    public function __construct($pdf, $filename)
    {
        $this->pdf = $pdf;
        $this->filename = $filename;
    }

    public function makeHydration()
    {
        $html = $this->mergeHtmlFromAllPages();

        $resultWithBrTags = $this->getPreparedDataFromHtml($html);
        if (!$resultWithBrTags) {
            return;
        }

        $html = str_replace('<br>', '', $html);
        $resultWithoutBrTags = $this->getPreparedDataFromHtml($html);
        if (!$resultWithoutBrTags) {
            return;
        }

        $this->setConnectionsRowNumber($resultWithoutBrTags);
        $this->setSummaryRowNumber($resultWithoutBrTags);
        $this->setSummaryServicesNetRowNumber($resultWithoutBrTags);
        $this->setProductsLastRowNumber();
        $this->setProductsFirstThTableRowNumber($resultWithoutBrTags);
        $this->hydrateProducts($resultWithoutBrTags);

        // if seller name is more than two rows removes first empty row
        if ($resultWithBrTags[3] == 'o.') {
            array_shift($resultWithBrTags);
        }

        $this->hydrateDataStructure['companyNip'] = str_replace('-', '', substr($resultWithBrTags[6], 5));

        $this->hydrateDataStructure['invoiceNr'] = substr($resultWithBrTags[14], 19);
        $this->hydrateDataStructure['dateOfInvoice'] = substr($resultWithBrTags[7], 20);
        $this->hydrateDataStructure['dateOfPayment'] = substr($resultWithBrTags[8], 22);
        $this->hydrateDataStructure['forTimeFrom'] = substr($resultWithBrTags[9], 15, 10);
        $this->hydrateDataStructure['forTimeTo'] = substr($resultWithBrTags[9], 31);

        $this->hydrateDataStructure['clientNip'] = substr($resultWithBrTags[10], 6);
        $this->hydrateDataStructure['clientNr'] = substr($resultWithBrTags[11], 17);
        $this->hydrateDataStructure['clientFullName'] = $resultWithBrTags[16];
        $this->hydrateDataStructure['clientAddress'] = $resultWithBrTags[17];
        $this->hydrateDataStructure['clientZipcode'] = substr($resultWithBrTags[18], 0, 6);
        $this->hydrateDataStructure['clientCity'] = substr($resultWithBrTags[18], 8);

        $this->hydrateDataStructure['connectionsNr'] = $resultWithoutBrTags[$this->connectionsRowNumber + 3];
        $this->setConnectionsTime($resultWithoutBrTags);

        $this->hydrateDataStructure['summaryServicesNetValueNet'] = $resultWithoutBrTags[$this->summaryServicesNetRowNumber + 1];
        $this->hydrateDataStructure['summaryServicesNetVat'] = $resultWithoutBrTags[$this->summaryServicesNetRowNumber + 2];
        $this->hydrateDataStructure['summaryServicesNetValueVat'] = $resultWithoutBrTags[$this->summaryServicesNetRowNumber + 3];
        $this->hydrateDataStructure['summaryServicesNetGrossValue'] = $resultWithoutBrTags[$this->summaryServicesNetRowNumber + 3];

        $this->hydrateDataStructure['summaryNetValue'] = $resultWithoutBrTags[$this->summaryRowNumber + 1];
        $this->hydrateDataStructure['summaryVatValue'] = $resultWithoutBrTags[$this->summaryRowNumber + 2];
        $this->hydrateDataStructure['summaryGrossValue'] = $resultWithoutBrTags[$this->summaryRowNumber + 3];
    }

    private function mergeHtmlFromAllPages()
    {
        $pages = $this->pdf->getPages();
        $html = '';

        for ($currentPageNumber = 1; $currentPageNumber <= $pages; $currentPageNumber++) {
            $html .= $this->pdf->html($currentPageNumber);
        }

        return $html;
    }

    private function getPreparedDataFromHtml($html)
    {
        preg_match_all('/>(.*)</U', $html, $matches);
        if (!isset($matches[1])) {
            $this->addError('Nie można odczytać danych z pliku');
            return null;
        }

        // cuts out repeatable fields
        return $this->cutOffRepeatablePages($matches[1]);
    }

    private function cutOffRepeatablePages($rows)
    {
        $companyName = $rows[2];

        $result = [];
        foreach ($rows as $key => $value) {
            if ($key > 2 && $value == $companyName) {
                break;
            }
            $result[] = $value;
        }

        return $result;
    }

    private function setConnectionsTime($data)
    {
        if (isset($data[$this->connectionsRowNumber + 4]) && $data[$this->connectionsRowNumber + 4]) {
            $this->hydrateDataStructure['connectionsTime'] = $data[$this->connectionsRowNumber + 4];
        }
    }

    private function hydrateProducts($data)
    {
        $products = [];
        $productsRows = [];
        $indexRows = -1;
        $indexColumns = 0;
        for ($i = $this->productsFirstThTableRowNumber + 7; $i <= $this->productsLastRowNumber; $i++) {
            if (!is_numeric($data[$i])) {
                $indexRows++;
                $indexColumns = 0;

                // deletes sign "-" from words where this sign was added at the end of line to wrap words
                preg_match_all('/[a-zA-Z]+-[a-zA-Z]+/', $data[$i], $matches);
                if (isset($matches[0])) {
                    for ($j = 0; $j < count($matches); $j++) {
                        $wordWithDeletedSign = str_replace('-', '', $matches[$j]);
                        $data[$i] = str_replace($matches[$j], $wordWithDeletedSign, $data[$i]);
                    }
                }

                // deletes everything what is before "—" sign
                $pos = strpos($data[$i], '—');
                if ($pos !== false) {
                    $data[$i] = substr($data[$i], $pos + 5);
                }

                $products[$indexRows]['title'] = $data[$i];

            } else {
                if ($indexColumns == 0) {
                    $products[$indexRows]['netValue'] = $data[$i];
                } elseif ($indexColumns == 1) {
                    $products[$indexRows]['vatPercentage'] = $data[$i];
                } elseif ($indexColumns == 2) {
                    $products[$indexRows]['vatValue'] = $data[$i];
                } elseif ($indexColumns == 3) {
                    $products[$indexRows]['grossValue'] = $data[$i];
                }
                $indexColumns++;
            }
            $productsRows[] = $data[$i];
        }


        // if product title is broken to pieces, it merges it together
        $result = [];
        $tempVal = [];
        foreach ($products as $key => $value) {
            if (count($products[$key]) == 1) {
                $tempVal[] = $value['title'];
            } else {
                if (count($tempVal)) {
                    $products[$key]['title'] = implode(' ', $tempVal) . ' ' . $value['title'];
                    $tempVal = [];
                }

                $result[] = $products[$key];
            }
        }

        $this->hydrateProductStructure = $result;
    }

    private function setProductsFirstThTableRowNumber($data)
    {
        foreach ($data as $key => $row) {
            if (substr($row, 0, 7) == 'Opłaty') {
                $this->productsFirstThTableRowNumber = $key;
                return;
            }
        }

        $this->addError('Nie znaleziono nagłówka tabeli produktów "Opłaty"');
    }

    private function setProductsLastRowNumber()
    {
        $this->productsLastRowNumber = $this->summaryServicesNetRowNumber - 1;
    }

    private function setSummaryRowNumber($data)
    {
        foreach ($data as $key => $row) {
            if (substr($row, 0, 5) == 'Razem') {
                $this->summaryRowNumber = $key;
                return;
            }
        }

        $this->addError('Nie znaleziono treści "Razem" w podsumowaniu płatności');
    }

    private function setSummaryServicesNetRowNumber($data)
    {
        foreach ($data as $key => $row) {
            if (strpos($row, 'podlegających') !== false) {
                $this->summaryServicesNetRowNumber = $key;
                return;
            }
        }

        $this->addError('Nie znaleziono treści "podlegających" w podsumowaniu płatności');
    }

    private function setConnectionsRowNumber($data)
    {
        foreach ($data as $key => $row) {
            if ($row == 'liczbapołączeń') {
                $this->connectionsRowNumber = $key;
                return;
            }
        }

        $this->addError('Nie znaleziono treści "liczba połączeń"');
    }

}