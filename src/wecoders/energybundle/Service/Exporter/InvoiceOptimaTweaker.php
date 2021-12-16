<?php

namespace Wecoders\EnergyBundle\Service\Exporter;

use TZiebura\ExporterBundle\Model\ExportData;
use TZiebura\ExporterBundle\Service\TweakerInterface;
use Wecoders\EnergyBundle\Entity\InvoiceSettlement;

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
            'WARTOSC AKCYZY',
            'DOKUMENTY WCHODZACE W SKLAD',
            'FAKTURA - DOKUMENTY WCHODZACE W SKLAD'
        ];
        foreach ($headers as $key => $header) {
            if (in_array($header, $deleteHeaders)) {
                unset($headers[$key]);
            }
        }

        foreach($data as &$dataRow) {
            $this->changeTypeTitle($dataRow);

            $tmp = null;

            if (
                array_key_exists('included_documents', $dataRow)
            ) {
                if (!$tmp) {
                    $tmp = new InvoiceSettlement();
                    $tmp->setIncludedDocumentsSerializedData($dataRow['included_documents']);
                }
                $dataRow['summary_net_value'] = number_format($dataRow['summary_net_value'] - $tmp->getIncludedDocumentsNetValue(), 2, '.', '');
                $dataRow['summary_vat_value'] = number_format($dataRow['summary_vat_value'] - $tmp->getIncludedDocumentsVatValue(), 2, '.', '');
                $dataRow['summary_gross_value'] = number_format($dataRow['summary_gross_value'] - $tmp->getIncludedDocumentsGrossValue(), 2, '.', '');
                $dataRow['excise_value'] = number_format($dataRow['excise_value'] - $tmp->getIncludedDocumentsExciseValue(), 2, '.', '');
                unset($dataRow['included_documents']);
            }

            if ($tmp) {
                unset($tmp);
            }


            $this->changeValuesOnCorrections($dataRow);
        }

        $exportData->setData($data);
        $exportData->setHeaders($headers);
    }

    private function changeTypeTitle(array &$dataRow)
    {
        if (isset($dataRow['type']) && $dataRow['type'] == 'ENERGY') {
            $dataRow['type'] = 'PRĄD';
        } elseif (isset($dataRow['type']) && $dataRow['type'] == 'GAS') {
            $dataRow['type'] = 'GAZ';
        }
    }

    private function changeValuesOnCorrections(array &$dataRow)
    {
        // correction
        if (isset($dataRow['invoice_id'])) {
            // calculate original invoice data
            $tmp = null;
            if (
                array_key_exists('invoice_included_documents', $dataRow)
            ) {
                if (!$tmp) {
                    $tmp = new InvoiceSettlement();
                    $tmp->setIncludedDocumentsSerializedData($dataRow['invoice_included_documents']);
                }
                $dataRow['invoice_summary_net_value'] = number_format($dataRow['invoice_summary_net_value'] - $tmp->getIncludedDocumentsNetValue(), 2, '.', '');
                $dataRow['invoice_summary_vat_value'] = number_format($dataRow['invoice_summary_vat_value'] - $tmp->getIncludedDocumentsVatValue(), 2, '.', '');
                $dataRow['invoice_summary_gross_value'] = number_format($dataRow['invoice_summary_gross_value'] - $tmp->getIncludedDocumentsGrossValue(), 2, '.', '');
                $dataRow['invoice_excise_value'] = number_format($dataRow['invoice_excise_value'] - $tmp->getIncludedDocumentsExciseValue(), 2, '.', '');

                unset($dataRow['invoice_included_documents']);
            }
            if ($tmp) {
                unset($tmp);
            }

            if (
                is_numeric($dataRow['summary_net_value']) && is_numeric($dataRow['invoice_summary_net_value']) &&
                is_numeric($dataRow['summary_vat_value']) && is_numeric($dataRow['invoice_summary_vat_value']) &&
                is_numeric($dataRow['summary_gross_value']) && is_numeric($dataRow['invoice_summary_gross_value'])
            ) {
                $dataRow['summary_net_value'] = number_format($dataRow['summary_net_value'] - $dataRow['invoice_summary_net_value'], 2, '.', '');
                $dataRow['summary_vat_value'] = number_format($dataRow['summary_vat_value'] - $dataRow['invoice_summary_vat_value'], 2, '.', '');
                $dataRow['summary_gross_value'] = number_format($dataRow['summary_gross_value'] - $dataRow['invoice_summary_gross_value'], 2, '.', '');

            } else {
                $dataRow['summary_net_value'] = '';
                $dataRow['summary_vat_value'] = '';
                $dataRow['summary_gross_value'] = '';
            }

            if (
                $dataRow['type'] == 'PRĄD' &&
                is_numeric($dataRow['excise_value']) && is_numeric($dataRow['invoice_excise_value'])
            ) {
//                $dataRow['exciseValue'] = $dataRow['exciseValue'] - $dataRow['invoice_excise_value'];
                $dataRow['excise_value'] = number_format($dataRow['excise_value'] - $dataRow['invoice_excise_value'], 2, '.', '');
            } else {
                $dataRow['excise_value'] = '';
            }

            unset($dataRow['invoice_summary_net_value']);
            unset($dataRow['invoice_summary_vat_value']);
            unset($dataRow['invoice_summary_gross_value']);
            unset($dataRow['invoice_excise_value']);
        }
    }
}