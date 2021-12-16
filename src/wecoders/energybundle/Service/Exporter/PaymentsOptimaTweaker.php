<?php

namespace Wecoders\EnergyBundle\Service\Exporter;

use TZiebura\ExporterBundle\Model\ExportData;
use TZiebura\ExporterBundle\Service\TweakerInterface;

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




            if ($dataRow['cg_id'] && !$dataRow['ce_id']) {
                $dataRow['energy_type'] = 'GAZ';
            } elseif ($dataRow['ce_id'] && !$dataRow['cg_id']) {
                $dataRow['energy_type'] = 'PRĄD';
            } elseif ($dataRow['ic_id']) {
                if ($dataRow['ic_type'] == 'ENERGY') {
                    $dataRow['energy_type'] = 'PRĄD';
                } elseif ($dataRow['ic_type'] == 'GAS') {
                    $dataRow['energy_type'] = 'GAZ';
                }
            }

            unset($dataRow['client_is_company']);
            unset($dataRow['cg_id']);
            unset($dataRow['ce_id']);
            unset($dataRow['client_client_surname']);
            unset($dataRow['client_company_name']);
            unset($dataRow['ic_id']);
            unset($dataRow['ic_type']);
        }

        $exportData->setData($data);
        $exportData->setHeaders([]);
    }
}