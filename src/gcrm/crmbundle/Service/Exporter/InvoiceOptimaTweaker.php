<?php

namespace GCRM\CRMBundle\Service\Exporter;

use TZiebura\ExporterBundle\Model\ExportData;
use TZiebura\ExporterBundle\Service\TweakerInterface;
use Wecoders\EnergyBundle\Service\Exporter\BaseTweaker;

class InvoiceOptimaTweaker extends BaseTweaker implements TweakerInterface
{
    public function tweak(ExportData $exportData)
    {
        $headers = $exportData->getHeaders();
        $data = $exportData->getData();

        $deleteHeaders = [
            'FAKTURA - KWOTA NETTO',
            'FAKTURA - KWOTA VAT',
            'FAKTURA - KWOTA BRUTTO',
        ];
        foreach ($headers as $key => $header) {
            if (in_array($header, $deleteHeaders)) {
                unset($headers[$key]);
            }
        }

        foreach($data as &$dataRow) {
            $this->changeValuesOnCorrections($dataRow);
        }

        $exportData->setData($data);
        $exportData->setHeaders($headers);
    }

    private function changeValuesOnCorrections(array &$dataRow)
    {
        // correction
        if (isset($dataRow['invoice_invoice_number'])) {
            if (
                is_numeric($dataRow['summaryNetValue']) && is_numeric($dataRow['invoice_summary_net_value']) &&
                is_numeric($dataRow['summaryVatValue']) && is_numeric($dataRow['invoice_summary_vat_value']) &&
                is_numeric($dataRow['summaryGrossValue']) && is_numeric($dataRow['invoice_summary_gross_value'])
            ) {
                $dataRow['summaryNetValue'] = $dataRow['summaryNetValue'] - $dataRow['invoice_summary_net_value'];
                $dataRow['summaryVatValue'] = $dataRow['summaryVatValue'] - $dataRow['invoice_summary_vat_value'];
                $dataRow['summaryGrossValue'] = $dataRow['summaryGrossValue'] - $dataRow['invoice_summary_gross_value'];
            } else {
                $dataRow['summaryNetValue'] = '';
                $dataRow['summaryVatValue'] = '';
                $dataRow['summaryGrossValue'] = '';
            }

            unset($dataRow['invoice_summary_net_value']);
            unset($dataRow['invoice_summary_vat_value']);
            unset($dataRow['invoice_summary_gross_value']);
        }
    }
}