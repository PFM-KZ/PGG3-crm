<?php

namespace GCRM\CRMBundle\Service\Exporter;

use TZiebura\ExporterBundle\Model\ExportData;
use TZiebura\ExporterBundle\Service\TweakerInterface;
use Wecoders\EnergyBundle\Service\Exporter\BaseTweaker;

class PaymentsOptimaTweaker extends BaseTweaker implements TweakerInterface
{
    public function tweak(ExportData $exportData)
    {
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
                $dataRow['client_id'] = '1';
            }

            unset($dataRow['client_is_company']);
            unset($dataRow['client_client_surname']);
            unset($dataRow['client_company_name']);
        }

        $exportData->setData($data);
        $exportData->setHeaders([]);
    }
}