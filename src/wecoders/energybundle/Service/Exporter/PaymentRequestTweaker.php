<?php

namespace Wecoders\EnergyBundle\Service\Exporter;

use TZiebura\ExporterBundle\Model\ExportData;
use TZiebura\ExporterBundle\Service\TweakerInterface;

class PaymentRequestTweaker extends BaseTweaker implements TweakerInterface
{
    public function tweak(ExportData $exportData)
    {
        $headers = $exportData->getHeaders();
        $data = $exportData->getData();

        foreach($data as &$dataRow) {
            $this->addOverdueDateOfPayment($dataRow);
            $dataRow['days_overdue_duplicates'] = $dataRow['days_overdue_duplicates'] ? 'TAK' : 'NIE';
        }

        $exportData->setData($data);
        $exportData->setHeaders($headers);
    }

    private function addOverdueDateOfPayment(array &$dataRow)
    {
        $isPaidKey = 'isPaid';
        $dateOfPaymentKey = 'dateOfPayment';

        if ($dataRow[$isPaidKey]) {
            $dataRow['days_overdue'] = 0;
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

        $dataRow['days_overdue'] = $diffDays;
    }
}