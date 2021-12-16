<?php

namespace GCRM\CRMBundle\Service\Exporter;

use TZiebura\ExporterBundle\Model\ExportData;
use TZiebura\ExporterBundle\Service\TweakerInterface;
use Wecoders\EnergyBundle\Service\Exporter\BaseTweaker;

class ContractorsOptimaTweaker extends BaseTweaker implements TweakerInterface
{
    public function tweak(ExportData $exportData)
    {
        $headers = $exportData->getHeaders();
        $data = $exportData->getData();

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