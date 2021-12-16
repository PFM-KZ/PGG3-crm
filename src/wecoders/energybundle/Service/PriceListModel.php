<?php

namespace Wecoders\EnergyBundle\Service;

use Doctrine\ORM\EntityManager;
use Wecoders\EnergyBundle\Entity\PriceList;
use Wecoders\EnergyBundle\Entity\PriceListData;
use Wecoders\EnergyBundle\Entity\PriceListDataAndTariff;
use Wecoders\EnergyBundle\Entity\PriceListDataAndYearWithPrice;
use Wecoders\EnergyBundle\Entity\Tariff;

class PriceListModel
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getActivePriceLists($energyType = null)
    {
        $parameters = ['showInAuthorization' => true];

        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from('WecodersEnergyBundle:PriceList', 'a')
            ->leftJoin('WecodersEnergyBundle:PriceListGroup', 'b', 'WITH', 'a.priceListGroup = b.id')
            ->where('a.showInAuthorization = :showInAuthorization')
            ->andWhere('b.showInAuthorization = :showInAuthorization')
        ;

        if ($energyType) {
            $q->andWhere('a.energyType = :energyType');
            $parameters['energyType'] = $energyType;
        }

        return $q->setParameters($parameters)->getQuery()->getResult();
    }

    public function getCurrentEnergyPricesByPriceListAndTariff(PriceList $priceList, Tariff $tariff, \DateTime $date, $contractType)
    {
        // check if tariff exist in price list in current year
        /** @var PriceListData $data */
        $data = $priceList->getPriceListDatas();
        if (!$data) {
            throw new \Exception('Price list data is empty');
        }

        /** @var PriceListData $priceListData */
        $energyPrices = [];
        foreach ($data as $priceListData) {
            $foundTariffSoCanCheckForCurrentYearIfExist = false;

            $tariffTypeCode = $priceListData->getTariffTypeCode();
            if (!$tariffTypeCode) {
                throw new \Exception('Price list data tariff type code is empty: ' . $priceList);
            }

            /** @var PriceListDataAndTariff $priceListDataAndTariffs */
            $priceListDataAndTariffs = $priceListData->getPriceListDataAndTariffs();
            if (!$priceListDataAndTariffs) {
                throw new \Exception('Price list data and tariffs are empty: '  . $priceList);
            }

            /** @var PriceListDataAndTariff $priceListDataAndTariff */
            foreach ($priceListDataAndTariffs as $priceListDataAndTariff) {
                /** @var Tariff $itemTariff */
                $itemTariff = $priceListDataAndTariff->getTariff();
                if (!$itemTariff) {
                    continue;
                }
                if ($itemTariff->getId() == $tariff->getId()) {
                    $foundTariffSoCanCheckForCurrentYearIfExist = true;
                    break;
                }
            }

            if ($foundTariffSoCanCheckForCurrentYearIfExist) {
                $priceListDataAndYearWithPrices = $priceListData->getPriceListDataAndYearWithPrices();
                if (!$priceListDataAndYearWithPrices) {
                    throw new \Exception('Price list data and year with prices are empty: ' . $priceList);
                }

                // search if year match with current year
                $date->format('Y');
                // if pricing for current year is not specified, it takes last pricing data
                $lastEnergyPricing = null;

                /** @var PriceListDataAndYearWithPrice $priceListDataAndYearWithPrice */
                $foundPricing = false;
                foreach ($priceListDataAndYearWithPrices as $priceListDataAndYearWithPrice) {
                    $year = $priceListDataAndYearWithPrice->getYear();
                    $grossValue = $priceListDataAndYearWithPrice->getGrossValue();
                    $netValue = $priceListDataAndYearWithPrice->getNetValue();

                    if ($contractType == 'ENERGY') {
                        if (!is_numeric($grossValue) || !is_numeric($netValue)) {
                            throw new \Exception('Price list data and year with prices are not set properly: ' . $priceList);
                        }

                        $lastEnergyPricing = [
                            'typeCode' => $tariffTypeCode,
                            'netValue' => $netValue,
                            'grossValue' => $grossValue,
                        ];

                        if (!$year) {
                            $energyPrices[] = [
                                'typeCode' => $tariffTypeCode,
                                'netValue' => $netValue,
                                'grossValue' => $grossValue,
                            ];
                            $foundPricing = true;
                            break;
                        }
                        if ($year == $date->format('Y')) {
                            $energyPrices[] = [
                                'typeCode' => $tariffTypeCode,
                                'netValue' => $netValue,
                                'grossValue' => $grossValue,
                            ];
                            $foundPricing = true;
                            break;
                        }
                    } else {
                        $energyPrices[] = [
                            'typeCode' => $tariffTypeCode,
                            'netValue' => $netValue,
                            'grossValue' => $grossValue,
                        ];
                        $foundPricing = true;
                        break;
                    }
                }

                if (!$foundPricing && $lastEnergyPricing) {
                    $energyPrices[] = $lastEnergyPricing;
                    $lastEnergyPricing = null;
                }
            }
        }
        if (!count($energyPrices)) {
            throw new \Exception('Energy prices not found for current price list: ' . $priceList . ' and tariff: ' . $tariff);
        }

        return $energyPrices;
    }

}