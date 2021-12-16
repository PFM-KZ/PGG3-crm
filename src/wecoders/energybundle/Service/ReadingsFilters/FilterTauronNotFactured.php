<?php

namespace Wecoders\EnergyBundle\Service\ReadingsFilters;

use Wecoders\EnergyBundle\Entity\EnergyData;
use Wecoders\EnergyBundle\Service\OsdModel;

class FilterTauronNotFactured implements ReadingsFiltersInterface
{
    private $filter;

    public function __construct(Filter $filter)
    {
        $this->filter = $filter;
    }

    public function getRecords()
    {
        $result = [];

        /** @var EnergyData $record */
        $records = $this->filter->getRecords();
        $isTauron = $records && count($records) && $records[0]->getCode() == OsdModel::OPTION_ELECTRICITY_TAURON ? true : false;

        if ($isTauron) {
            // if first settlement system must check backward from last record to first till finds F record or K
            if ($this->filter->getIsFirstSettlement()) {
                $records = array_reverse($records);

                $found = false;
                foreach ($records as $record) {
                    if ($this->filter->getIsLastSettlement()) {
                        if ($record->getBillingStatus() == 'F' || $record->getReadingTypeOriginal() == 'K') {
                            $found = true;
                        }
                    } else {
                        if ($record->getBillingStatus() == 'F') {
                            $found = true;
                        }
                    }

                    if ($found) {
                        $result[] = $record;
                    }
                }

                // reverse back
                $result = array_reverse($result);
            } else {
                foreach ($records as $record) {
                    if ($this->filter->getIsLastSettlement()) {
                        if ($record->getBillingStatus() != 'F' && $record->getReadingTypeOriginal() != 'K') {
                            continue;
                        }
                    } else {
                        if ($record->getBillingStatus() != 'F') {
                            continue;
                        }
                    }

                    $result[] = $record;
                }
            }

            $this->filter->setRecords($result);
        }

        return $this->filter->getRecords();
    }

}