<?php

namespace Wecoders\EnergyBundle\Service\Exporter;

use TZiebura\ExporterBundle\Model\ExportData;
use TZiebura\ExporterBundle\Service\TweakerInterface;

class InvoiceCollectiveTweaker extends BaseTweaker implements TweakerInterface
{
    public function tweak(ExportData $exportData)
    {
        $headers = $exportData->getHeaders();
        $data = $exportData->getData();

        foreach($data as &$dataRow) {
            $this->manageInvoicesNumbers($dataRow);
            $this->changeTypeTitle($dataRow);
        }
        $this->deleteDuplicateHeaders($headers);

        $exportData->setData($data);
        $exportData->setHeaders($headers);
    }

    private function manageInvoicesNumbers(array &$dateRow)
    {
        if (!$dateRow['invoices_numbers']) {
            return;
        }

        $list = unserialize($dateRow['invoices_numbers']);
        if (!$list) {
            return;
        }

        $result = [];
        foreach ($list as $item) {
            $data = unserialize($item);
            $result[] = $data['number'];
        }

        $dateRow['invoices_numbers'] = implode(', ', $result);
    }

    private function deleteDuplicateHeaders(array &$headers)
    {
        $headers = array_values(array_unique($headers));
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