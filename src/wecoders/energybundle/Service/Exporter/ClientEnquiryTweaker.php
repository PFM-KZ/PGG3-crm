<?php

namespace Wecoders\EnergyBundle\Service\Exporter;

use TZiebura\ExporterBundle\Model\ExportData;
use TZiebura\ExporterBundle\Service\TweakerInterface;
use Wecoders\EnergyBundle\Entity\InvoiceSettlement;
use Wecoders\EnergyBundle\Model\SettlementIncludedDocument;

class ClientEnquiryTweaker extends BaseTweaker implements TweakerInterface
{

    public function tweak(ExportData $exportData)
    {
        $data = $exportData->getData();

        foreach($data as &$dataRow) {
            $this->changeEnergyTypeLabel($dataRow);
            $this->changeClientTypeLabel($dataRow);
            $this->changeMarketingAgreementLabel($dataRow);
        }
        $exportData->setData($data);
    }

    private function changeEnergyTypeLabel(array &$dataRow)
    {
        if (isset($dataRow['energyType']) && $dataRow['energyType'] == 1) {
            $dataRow['energyType'] = 'Prąd';
        } elseif (isset($dataRow['energyType']) && $dataRow['energyType'] == 2) {
            $dataRow['energyType'] = 'Gaz';
        }
    }

    private function changeClientTypeLabel(array &$dataRow)
    {
        if (isset($dataRow['clientType']) && $dataRow['clientType'] == 1) {
            $dataRow['clientType'] = 'Osoba fizyczna';
        } elseif (isset($dataRow['clientType']) && $dataRow['clientType'] == 2) {
            $dataRow['clientType'] = 'Firma';
        }
    }

    private function changeMarketingAgreementLabel(array &$dataRow)
    {
        if (isset($dataRow['isRebateMarketingAgreement']) && $dataRow['isRebateMarketingAgreement'] == 1) {
            $dataRow['isRebateMarketingAgreement'] = 'Tak';
        } else {
            $dataRow['isRebateMarketingAgreement'] = 'Nie';
        }
    }
}