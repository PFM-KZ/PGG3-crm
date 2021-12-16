<?php

namespace Wecoders\EnergyBundle\Service\Exporter;

use TZiebura\ExporterBundle\Model\ExportData;
use TZiebura\ExporterBundle\Service\TweakerInterface;

class ClientProcessTerminatedTemplateTweaker extends BaseTweaker implements TweakerInterface
{
    public function tweak(ExportData $exportData)
    {
        $headers = $exportData->getHeaders();
        $data = $exportData->getData();

        $this->deleteDuplicateHeaders($headers);
        $this->deleteCounterNrHeader($headers);
        $exportData->setHeaders(array_values($headers));

        foreach($data as &$dataRow) {
            $this->deleteNullContract($dataRow);
            $this->combinePpAddress($dataRow);
            // this deletes index from dataRow, execute this last
            $this->determinePpe($dataRow);
        }

        $exportData->setData($data);
    }

    private function combinePpAddress(array &$dataRow)
    {
        $delimeter = '|';

        $streetIndex = 0;
        $houseNrIndex = 1;
        $apartmentNrIndex = 2;
        $zipCodeIndex = 3;
        $cityIndex = 4;

        $dataRowFullPpIndex = 2;
        if(!$dataRow[$dataRowFullPpIndex]) {
            $dataRow[$dataRowFullPpIndex] = '';
            return;
        }
        $exploded = explode($delimeter, $dataRow[$dataRowFullPpIndex]);

        if($exploded[$houseNrIndex] && $exploded[$apartmentNrIndex]) {
            $houseOrApartmentNr = $exploded[$houseNrIndex] . '/' . $exploded[$apartmentNrIndex]; 
        } elseif($exploded[$houseNrIndex]) {
            $houseOrApartmentNr = $exploded[$houseNrIndex];
        } else {
            $houseOrApartmentNr = null;
        }
        

        if(!$exploded[$streetIndex] || !$houseOrApartmentNr || !$exploded[$zipCodeIndex] || !$exploded[$cityIndex]) {
            $dataRow[$dataRowFullPpIndex] = '';
        } else {
            $dataRow[$dataRowFullPpIndex] = $exploded[$streetIndex] . ' ' . $houseOrApartmentNr . ' ' . $exploded[$zipCodeIndex] . ' ' . $exploded[$cityIndex];
        }
    }

    private function determinePpe(array &$dataRow)
    {
        $typeIndex = 7;
        if($dataRow[$typeIndex] === 'GAZ') { // skip determining ppe in case of contract gas
            return;
        }

        $ppIndex = 3;
        $counterIndex = 4;

        if ($dataRow[$ppIndex]) {
            unset($dataRow[$counterIndex]);
        } else if ($dataRow[$counterIndex]) {
            unset($dataRow[$ppIndex]);
        } else { // unset whatever, just to reduce the array count
            unset($dataRow[$counterIndex]);
        }
        
        $dataRow = array_values($dataRow);
    }

    private function deleteCounterNrHeader(array &$headers)
    {
        $ppCounterIndexLabel = 'ce.ppCounterNr';
        foreach($headers as $index => $header) {
            if($header === $ppCounterIndexLabel) {
                unset($headers[$index]);
            }
        }
    }

    private function deleteNullContract(array &$dataRow)
    {
        $contractType = 'exists';
        if(!$dataRow['contract_gas_contract_number']) {
            $pattern = '/contract_gas/';
        } elseif (!$dataRow['contract_energy_contract_number']) {
            $pattern = '/contract_energy/';
        } else {
            $pattern = '/contract_gas/'; // delete whatever
            $contractType = null;
        }


        $this->deleteContract($pattern, $dataRow);

        if(!$contractType) {
            $dataRow[8] = '';
        }
    }

    private function deleteDuplicateHeaders(array &$headers)
    {
        $deleteStart = 'Adres PP';
        $deleting = false;
        foreach($headers as $index => $header) {
            if($header === $deleteStart) {
                if($deleting) {
                    $deleting = false;
                } else {
                    $deleting = true;
                }
            }

            if($deleting) {
                unset($headers[$index]);
            }
        }
        $headers = array_values($headers);
    }
}