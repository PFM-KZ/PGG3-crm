<?php

namespace Wecoders\EnergyBundle\Service\Exporter;

use TZiebura\ExporterBundle\Model\ExportData;
use TZiebura\ExporterBundle\Service\TweakerInterface;

// TODO finish tweaker for actual status
class ClientTweaker extends BaseTweaker implements TweakerInterface
{
    private $collectionHeadings;

    private $deleteHeaders = [
        'CENNIKI',
        'TARYFY DYSTRYBUCYJNE',
        'TARYFY SPRZEDAWCY',
        'PP'
    ];

    public function tweak(ExportData $exportData)
    {
        $data = $exportData->getData();
        foreach($data as &$dataRow) {
            $this->deleteNullContract($dataRow);
            $this->translate($dataRow);
            $this->setActualStatus($dataRow);
//            $this->setCollectionData($dataRow, 'contract_and_price_lists', [
//                'fromDate' => 'Cennik od',
//                'title' => 'Cennik'
//            ], 5);
//            $this->setCollectionData($dataRow, 'contract_and_distribution_tariffs', [
//                'fromDate' => 'Taryfa dystrybucyjna od',
//                'title' => 'Taryfa dystrybucyjna'
//            ], 3);
//            $this->setCollectionData($dataRow, 'contract_and_seller_tariffs', [
//                'fromDate' => 'Taryfa sprzedawcy od',
//                'title' => 'Taryfa sprzedawcy'
//            ], 3);
//            $this->setCollectionData($dataRow, 'contract_and_ppcodes', [
//                'fromDate' => 'Kod PP od',
//                'title' => 'Kod PP'
//            ], 3);
        }

        $exportData->setData($data);

        $headers = $exportData->getHeaders();

        // delete headers
        foreach ($headers as $key => $header) {
            if (in_array($header, $this->deleteHeaders)) {
                unset($headers[$key]);
            }
        }

        $insertIndex = $this->deleteDuplicateHeaders($headers);


        // adds collection headings
//        if (isset($this->collectionHeadings['contract_and_price_lists'])) {
//            foreach ($this->collectionHeadings['contract_and_price_lists'] as $key => $value) {
//                array_splice($headers, $key, 0, $value);
//            }
//        }
//
//        if (isset($this->collectionHeadings['contract_and_distribution_tariffs'])) {
//            foreach ($this->collectionHeadings['contract_and_distribution_tariffs'] as $key => $value) {
//                array_splice($headers, $key, 0, $value);
//            }
//        }
//
//        if (isset($this->collectionHeadings['contract_and_seller_tariffs'])) {
//            foreach ($this->collectionHeadings['contract_and_seller_tariffs'] as $key => $value) {
//                array_splice($headers, $key, 0, $value);
//            }
//        }
//
//        if (isset($this->collectionHeadings['contract_and_ppcodes'])) {
//            foreach ($this->collectionHeadings['contract_and_ppcodes'] as $key => $value) {
//                array_splice($headers, $key, 0, $value);
//            }
//        }

        $exportData->setHeaders($headers);

        $this->addPostponedDeadlines($exportData);
    }

    private function translate(array &$dataRow)
    {
        if (!isset($dataRow['contract_type'])) {
            return;
        }

        $dataRow['contract_type'] = str_replace('ENERGY', 'PRĄD', $dataRow['contract_type']);
        $dataRow['contract_type'] = str_replace('GAS', 'GAZ', $dataRow['contract_type']);
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

    private function setActualStatus(array &$dataRow)
    {
        $aliasToReplace = 'contract_actual_status';

        $departmentAndStatus = [
            'Finansowy' => 'contract_status_contract_finances',
            'Procesowy' => 'contract_status_contract_process',
            'Kontrola' => 'contract_status_contract_control',
            'Administracja' => 'contract_status_contract_administration',
            'Weryfikacja' => 'contract_status_contract_verification',
            'Autoryzacja' => 'contract_status_contract_authorization',
        ];

        $canCheck = false;
        foreach($departmentAndStatus as $department => $status) {
            $statusFetched = $dataRow[$status];

            if ($department == $dataRow['contract_status_department']) {
                if (!$statusFetched) {
                    $canCheck = true;
                    continue;
                }
                $dataRow[$aliasToReplace] = $statusFetched;
            }

            if ($canCheck) {
                $dataRow[$aliasToReplace] = $statusFetched;
                break;
            }
        }
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

        $insertIndex = $this->deleteContract($pattern, $dataRow);
//        array_splice($dataRow, $insertIndex, 0, $contractType);
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

    private function addPostponedDeadlines(ExportData $exportData)
    {
        $headers = $exportData->getHeaders();
        $data = $exportData->getData();
        foreach($data as &$dataRow) {
            $postponedDeadlinesKey = 'contract_postponed_deadlines'; // this can change when order in config is changed(if it changes use array_splice)
            $postponedDeadlines = $dataRow[$postponedDeadlinesKey];

            if (!$postponedDeadlines || !count(unserialize($postponedDeadlines))) { // insert empty values
                $dataRow[$postponedDeadlinesKey] = '';
                $dataRow[] = '';
                $dataRow[] = '';
                $dataRow[] = '';
                $dataRow[] = '';
            } else { // insert actual values
                $unserialized = unserialize($postponedDeadlines);
                $newest = $unserialized[count($unserialized) - 1];
                
                $dataRow[$postponedDeadlinesKey] = $newest['isTerminationSent'] == 1 ? 'TAK' : ($newest['isTerminationSent'] == 0 ? 'NIE' : '');
                $dataRow[] = $newest['terminationCreatedDate'] ? $newest['terminationCreatedDate']->format('Y-m-d') : '';
                $dataRow[] = $newest['isProposalOsdSent'] == 1 ? 'TAK' : ($newest['isProposalOsdSent'] == 0 ? 'NIE' : '');
                $dataRow[] = $newest['plannedActivationDate'] ? $newest['plannedActivationDate']->format('Y-m-d') : '';
                $dataRow[] = $newest['proposalStatus'] == 1 ? 'POZYTYWNY' : ($newest['proposalStatus'] == 0 ? 'NEGATYWNY' : '');
            }
        }

        $headers = array_merge(
            $headers,
            [
                'Data wysłania wypowiedzenia(Zmiana terminu)',
                'Wysłano wniosek na OSD(Zmiana terminu)',
                'Planowana data uruchomienia(Zmiana terminu)',
                'Status wniosku(Zmiana terminu)',
            ]
        );

        $exportData->setHeaders($headers);
        $exportData->setData($data);
    }
}