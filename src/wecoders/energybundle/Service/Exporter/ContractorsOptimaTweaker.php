<?php

namespace Wecoders\EnergyBundle\Service\Exporter;

use TZiebura\ExporterBundle\Model\ExportData;
use TZiebura\ExporterBundle\Service\TweakerInterface;

class ContractorsOptimaTweaker extends BaseTweaker implements TweakerInterface
{
    public function tweak(ExportData $exportData)
    {
        $headers = $exportData->getHeaders();
        $data = $exportData->getData();

        $deleteHeaders = [
            'ID UMOWY PRAD',
            'ID UMOWY GAZ',
        ];
        foreach ($headers as $key => $header) {
            if (in_array($header, $deleteHeaders)) {
                unset($headers[$key]);
            }
        }

        foreach($data as &$dataRow) {
            $isCompany = $dataRow['client_is_company'];

            if ($isCompany) {
                $dataRow['client_client_name'] = $dataRow['client_client_company_name'];
                $dataRow['client_client_surname'] = '';
                $dataRow['client_client_company_name'] = '';
            } else {
                $dataRow['client_client_name'] = $dataRow['client_client_name'] . ' ' . $dataRow['client_client_surname'];
                $dataRow['client_client_surname'] = '';
                $dataRow['client_client_company_name'] = '';
            }


            if ($dataRow['cg_id'] && !$dataRow['ce_id']) {
                $dataRow['energy_type'] = 'GAZ';
            } elseif ($dataRow['ce_id'] && !$dataRow['cg_id']) {
                $dataRow['energy_type'] = 'PRĄD';
            }

            unset($dataRow['cg_id']);
            unset($dataRow['ce_id']);

            // is client not a company
            $dataRow['client_is_company'] = !(((int) $dataRow['client_is_company']) ? "1" : "0");
            if ($dataRow['client_is_company']) {
                $dataRow['client_is_company'] = "1";
            } else {
                $dataRow['client_is_company'] = "0";
            }
        }

        $exportData->setData($data);
        $exportData->setHeaders($headers);
    }
}