<?php

namespace Wecoders\EnergyBundle\Service\Exporter;

use TZiebura\ExporterBundle\Model\ExportData;
use TZiebura\ExporterBundle\Service\TweakerInterface;
use Wecoders\EnergyBundle\Entity\InvoiceSettlement;
use Wecoders\EnergyBundle\Model\SettlementIncludedDocument;

class InvoiceTweaker extends BaseTweaker implements TweakerInterface
{
    private $deleteHeaders = [
        'DOKUMENTY WCHODZACE W SKLAD',
    ];

    public function tweak(ExportData $exportData)
    {
        $headers = $exportData->getHeaders();
        // delete headers
        foreach ($headers as $key => $header) {
            if (in_array($header, $this->deleteHeaders)) {
                unset($headers[$key]);
            }
        }
        $data = $exportData->getData();

        foreach($data as &$dataRow) {
            if (array_key_exists('modified_to_pay', $dataRow)) {
                if (isset($dataRow['modified_to_pay']) && is_numeric($dataRow['modified_to_pay'])) {
                    $dataRow['modified_to_pay'] = number_format($dataRow['summary_gross_value'] + $dataRow['modified_to_pay'], 2, '.', '');
                } else {
                    $dataRow['modified_to_pay'] = $dataRow['summary_gross_value'];
                }
            }

            $tmp = null;

            if (
                array_key_exists('included_documents_net_value', $dataRow) &&
                array_key_exists('included_documents_vat_value', $dataRow) &&
                array_key_exists('included_documents_gross_value', $dataRow)
            ) {
                if (!$tmp) {
                    $tmp = new InvoiceSettlement();
                    $tmp->setIncludedDocumentsSerializedData($dataRow['included_documents']);
                }
                $dataRow['included_documents_net_value'] = $tmp->getIncludedDocumentsNetValue();
                $dataRow['included_documents_vat_value'] = $tmp->getIncludedDocumentsVatValue();
                $dataRow['included_documents_gross_value'] = $tmp->getIncludedDocumentsGrossValue();
                $dataRow['included_documents_excise_value'] = $tmp->getIncludedDocumentsExciseValue();

                $dataRow['included_documents_net_value_diff'] = number_format($dataRow['summary_net_value'] - $dataRow['included_documents_net_value'], 2, '.', '');
                $dataRow['included_documents_vat_value_diff'] = number_format($dataRow['summary_vat_value'] - $dataRow['included_documents_vat_value'], 2, '.', '');
                $dataRow['included_documents_gross_value_diff'] = number_format($dataRow['summary_gross_value'] - $dataRow['included_documents_gross_value'], 2, '.', '');
                $dataRow['included_documents_excise_value_diff'] = number_format($dataRow['excise_value'] - $dataRow['included_documents_excise_value'], 2, '.', '');
            }

            if (
                array_key_exists('included_document_numbers', $dataRow)
            ) {
                if (!$tmp) {
                    $tmp = new InvoiceSettlement();
                    $tmp->setIncludedDocumentsSerializedData($dataRow['included_documents']);
                }
                $dataRow['included_document_numbers'] = $tmp->getIncludedDocumentsNumbersValue();
            }

            if ($tmp) {
                unset($dataRow['included_documents']);
                unset($tmp);
            }

            $this->deleteNullContract($dataRow);
            $this->changeTypeTitle($dataRow);
        }
        $this->deleteDuplicateHeaders($headers);

        $exportData->setData($data);
        $exportData->setHeaders($headers);
    }

    private function deleteDuplicateHeaders(array &$headers)
    {
        $headers = array_values(array_unique($headers));
    }

    private function deleteNullContract(array &$dataRow)
    {
        if(isset($dataRow['contract_gas_contract_number']) && !$dataRow['contract_gas_contract_number']) {
            $pattern = '/contract_gas/';
        } elseif (isset($dataRow['contract_energy_contract_number']) && !$dataRow['contract_energy_contract_number']) {
            $pattern = '/contract_energy/';
        } else {
            $pattern = '/contract_gas/'; // delete whatever
        }

        $this->deleteContract($pattern, $dataRow);
    }

    private function changeTypeTitle(array &$dataRow)
    {
        if (isset($dataRow['type']) && $dataRow['type'] == 'ENERGY') {
            $dataRow['type'] = 'PRĄD';
        } elseif (isset($dataRow['type']) && $dataRow['type'] == 'GAS') {
            $dataRow['type'] = 'GAZ';
        }
    }
}