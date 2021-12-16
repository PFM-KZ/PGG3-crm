<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\ContractEnergy;
use GCRM\CRMBundle\Entity\ContractEnergyAndPriceList;
use GCRM\CRMBundle\Entity\ContractEnergyBase;
use GCRM\CRMBundle\Entity\ContractGasAndPriceList;
use GCRM\CRMBundle\Service\ContractModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wecoders\EnergyBundle\Entity\ContractEnergyInterface;
use Wecoders\EnergyBundle\Entity\PriceList;

class DuplicatePriceListsCommand extends Command
{
    private $em;
    private $contractModel;

    public function __construct(EntityManager $em, ContractModel $contractModel)
    {
        $this->em = $em;
        $this->contractModel = $contractModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('appbundle:duplicate-price-lists')
            ->setDescription('Duplicate price lists for given price list ID');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $priceListsToCopyIds = [];
        $index = 1;

        foreach($priceListsToCopyIds as $priceListToCopyId){

            $originalPriceList = $this->em->getRepository('Wecoders\EnergyBundle\Entity\PriceList')->findOneBy(['id' => $priceListToCopyId]);
            $originalPriceListDatas = $this->em->getRepository('Wecoders\EnergyBundle\Entity\PriceListData')->findBy(['priceList' => $priceListToCopyId]);
            $originalPriceListAndServiceDatas = $this->em->getRepository('Wecoders\EnergyBundle\Entity\PriceListAndServiceData')->findBy(['priceList' => $priceListToCopyId]);
            $originalPriceListSubscriptions = $this->em->getRepository('Wecoders\EnergyBundle\Entity\PriceListSubscription')->findBy(['priceList' => $priceListToCopyId]);
//            $originalPriceListGroup = $this->em->getRepository('Wecoders\EnergyBundle\Entity\PriceListGroup')->findBy(['priceList' => $priceListToCopyId]);

            // clone price list
            $duplicatePriceList = clone $originalPriceList;
            $duplicatePriceList->setId(null);
            $duplicatePriceList->setShowInAuthorization(false);
            $duplicatePriceList->setTitle($duplicatePriceList->getTitle().' - aktualizacja');
            $this->em->persist($duplicatePriceList);
            $this->em->flush($duplicatePriceList);

            // clone price list datas
            foreach ($originalPriceListDatas as $originalPriceListData){
                $originalPriceListDataTariffs = $this->em->getRepository('Wecoders\EnergyBundle\Entity\PriceListDataAndTariff')->findBy(['priceListData' => $originalPriceListData]);
                $originalPriceListDataAndYearWithPrices = $this->em->getRepository('Wecoders\EnergyBundle\Entity\PriceListDataAndYearWithPrice')->findBy(['priceListData' => $originalPriceListData]);

                $duplicatePriceListData = clone $originalPriceListData;
                $duplicatePriceListData->setPriceList($duplicatePriceList);
                $this->em->persist($duplicatePriceListData);
                $this->em->flush($duplicatePriceListData);

                // clone price list service data tariffs
                foreach ($originalPriceListDataTariffs as $originalPriceListDataTariff){
                    $duplicatePriceListDataTariff = clone $originalPriceListDataTariff;
                    $duplicatePriceListDataTariff->setPriceListData($duplicatePriceListData);
                    $this->em->persist($duplicatePriceListDataTariff);
                    $this->em->flush($duplicatePriceListDataTariff);
                }

                // clone price list service data with year
                foreach ($originalPriceListDataAndYearWithPrices as $originalPriceListDataAndYearWithPrice){
                    $duplicatePriceListDataAndYearWithPrice = clone $originalPriceListDataAndYearWithPrice;
                    $duplicatePriceListDataAndYearWithPrice->setPriceListData($duplicatePriceListData);
                    $this->em->persist($duplicatePriceListDataAndYearWithPrice);
                    $this->em->flush($duplicatePriceListDataAndYearWithPrice);
                }
            }

            // clone price list service datas
            foreach ($originalPriceListAndServiceDatas as $originalPriceListAndServiceData){
                $duplicatePriceListAndServiceData = clone $originalPriceListAndServiceData;
                $duplicatePriceListAndServiceData->setPriceList($duplicatePriceList);
                $this->em->persist($duplicatePriceListAndServiceData);
                $this->em->flush($duplicatePriceListAndServiceData);
            }


            // clone price list service datas
            foreach ($originalPriceListSubscriptions as $originalPriceListSubscription){
                $duplicatePriceListSubscription = clone $originalPriceListSubscription;
                $duplicatePriceListSubscription->setPriceList($duplicatePriceList);
                $this->em->persist($duplicatePriceListSubscription);
                $this->em->flush($duplicatePriceListSubscription);
            }



            dump($index);
            $index++;
        }

        dump('Success');
    }
}