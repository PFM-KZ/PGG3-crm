<?php

namespace Wecoders\EnergyBundle\Service\Exporter;

use TZiebura\ExporterBundle\Model\ExportData;
use TZiebura\ExporterBundle\Service\TweakerInterface;

class DebitNoteTweaker extends BaseTweaker implements TweakerInterface
{
    private $deleteHeaders = [
        'TYP UMOWY GAZ',
        'TYP UMOWY PRAD',
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

        $headers[] = 'Dni po terminie płatności';

        foreach($data as &$dataRow) {
            $this->addOverdueDateOfPayment($dataRow);

            if ($dataRow['contract_gas_type']) {
                $dataRow['contract_type'] = 'GAZ';
            } elseif ($dataRow['contract_energy_type']) {
                $dataRow['contract_type'] = 'PRĄD';
            }
            unset($dataRow['contract_gas_type']);
            unset($dataRow['contract_energy_type']);
        }


        $exportData->setData($data);
        $exportData->setHeaders($headers);
    }

    private function addOverdueDateOfPayment(array &$dataRow)
    {
        $isPaidKey = 'isPaid';
        $dateOfPaymentKey = 'dateOfPayment';

        if ($dataRow[$isPaidKey]) {
            $dataRow['overdueDateOfPayment'] = 0;
            return;
        }

        $dateStart = $dataRow[$dateOfPaymentKey];
        $dateStart = $dateStart->setTime(0,0);
        $dateEnd = (new \DateTime())->setTime(0,0);

        if ($dateStart < $dateEnd) {
            $diff = $dateStart->diff($dateEnd);
            $diffDays = $diff->days;
        } else {
            $diffDays = 0;
        }

        $dataRow['overdueDateOfPayment'] = $diffDays;
    }
}