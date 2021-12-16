<?php

namespace Wecoders\EnergyBundle\Service\Exporter;

use TZiebura\ExporterBundle\Model\ExportData;
use TZiebura\ExporterBundle\Service\TweakerInterface;

class ContractAndSellerTariffsTweaker extends BaseTweaker implements TweakerInterface
{
    private $collectionHeadings;

    private $deleteHeaders = [
        'TARYFY SPRZEDAWCY',
    ];

    public function tweak(ExportData $exportData)
    {
        $data = $exportData->getData();
        foreach($data as &$dataRow) {
            $this->deleteNullContract($dataRow);
            $this->setCollectionData($dataRow, 'contract_and_seller_tariffs', [
                'fromDate' => 'Taryfa sprzedawcy od',
                'title' => 'Taryfa sprzedawcy'
            ], 3);
        }

        $exportData->setData($data);

        $headers = $exportData->getHeaders();

        // delete headers
        foreach ($headers as $key => $header) {
            if (in_array($header, $this->deleteHeaders)) {
                unset($headers[$key]);
            }
        }

        $this->deleteDuplicateHeaders($headers);


        // adds collection headings
        if (isset($this->collectionHeadings['contract_and_seller_tariffs'])) {
            foreach ($this->collectionHeadings['contract_and_seller_tariffs'] as $key => $value) {
                array_splice($headers, $key, 0, $value);
            }
        }

        $exportData->setHeaders($headers);
    }

    private function setCollectionData(array &$dataRow, $collectionFieldName, array $fetchRows, $generateColumnsNumber)
    {
        // fetch collection items from tokens, hydrate it to list
        $collection = $dataRow[$collectionFieldName];
        $list = explode('###', $collection);

        $data = [];
        foreach ($list as $item) {
            // omit empty rows (created by commas with group concate)
            if (strlen($item) <= 1) {
                continue;
            }

            $rowData = [];
            foreach ($fetchRows as $key => $fetchRow) {
                $pattern = '/<' . $key . '>(.*)<\/' . $key .'>/';
                $matches = [];
                preg_match($pattern, $item, $matches);
                $rowData[$key] = $matches[1];
            }
            $data[] = $rowData;
        }


        // if data is higher than generateColumnsNumber, shrink it to the generateColumnsNumber value
        if (count($data) > $generateColumnsNumber) {
            $newData = [];
            for ($i = 0; $i < $generateColumnsNumber; $i++) {
                $newData[] = $data[$i];
            }
            $data = $newData;
        }




        // generate empty data to match generate columns number
        // it must be equal for every record to match the same columns
        $emptyRow = [];
        foreach ($fetchRows as $key => $value) {
            $emptyRow[$key] = '';
        }

        if (count($data) < $generateColumnsNumber) {
            $generateEmptyRowsNumber = $generateColumnsNumber - count($data);
            for ($i = 0; $i < $generateEmptyRowsNumber; $i++) {
                $data[] = $emptyRow;
            }
        }



        $applyData = [];
        $addedIndex = 0;

        $headersData = [];
        foreach ($data as $dataItem) {
            if ($addedIndex == $generateColumnsNumber) {
                break;
            }

            foreach ($dataItem as $key => $value) {
                $finalKey = $collectionFieldName . '_' . $addedIndex . '_' . $key;
                $applyData[$finalKey] = $value;

                $headerName = $fetchRows[$key] . ' ' . $addedIndex;
                $headersData[$finalKey] = $headerName;
            }
            $addedIndex++;
        }



        // fetch position of field to insert generated collection data
        $startingIndex = $this->getPosition($dataRow, $collectionFieldName);
        // apply data to row
        foreach ($applyData as $key => $value) {
            array_splice($dataRow, $startingIndex, 0, $value);
            $startingIndex++;
        }

        // manage headings, set up for first time only
        if (!isset($this->collectionHeadings[$collectionFieldName])) {
            $startingIndex = $this->getPosition($dataRow, $collectionFieldName);
            $appendedDataWithIndexes = [];
            foreach ($headersData as $key => $value) {
                $appendedDataWithIndexes[$startingIndex] = $value;
                $startingIndex++;
            }

            $this->collectionHeadings[$collectionFieldName] = $appendedDataWithIndexes;
        }

        // collection is already appended, so row can be unset
        unset($dataRow[$collectionFieldName]);
    }

    private function getPosition(array &$dataRow, $needle)
    {
        $index = 0;
        foreach ($dataRow as $key => $value) {
            if ($key == $needle) {
                return $index;
            }
            $index++;
        }

        return null;
    }

    private function deleteDuplicateHeaders(array &$headers)
    {
        $deleteStart = 'ID umowy';
        $deleting = false;
        $insertIndex = 0;
        foreach($headers as $index => $header) {
            if($header === $deleteStart) {
                if($deleting) {
                    $deleting = false;
                } else {
                    $insertIndex = $index; // get the index of first duplicate header
                    $deleting = true;
                }
            }

            if($deleting) {
                unset($headers[$index]);
            }
        }
        $headers = array_values($headers);

        return $insertIndex;
    }

    private function deleteNullContract(array &$dataRow)
    {
        if(!$dataRow['contract_gas_id']) {
            $pattern = '/contract_gas/';
        } elseif (!$dataRow['contract_energy_id']) {
            $pattern = '/contract_energy/';
        } else {
            $pattern = '/contract_gas/'; // delete whatever
        }

        $this->deleteContract($pattern, $dataRow);
    }

    protected function deleteContract($pattern, array &$dataRow)
    {
        $insertionIndex = 0; // where to insert contractType
        $unsetValues = 0; // count the offset

        $tmpDataRow = [];

        foreach($dataRow as $key => $value) {
            if(preg_match($pattern, $key)) {
                unset($dataRow[$key]);
                $unsetValues += 2;
                $insertionIndex++;
                continue;
            }

            $key = str_replace('contract_gas', 'contract', $key);
            $key = str_replace('contract_energy', 'contract', $key);
            $tmpDataRow[$key] = $value;

            $insertionIndex++;
        }

        $dataRow = $tmpDataRow;
        return $insertionIndex - $unsetValues;
    }

}