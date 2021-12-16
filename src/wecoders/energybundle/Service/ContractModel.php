<?php

namespace Wecoders\EnergyBundle\Service;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\ClientAndContractInterface;
use GCRM\CRMBundle\Entity\ContractEnergyBase;

class ContractModel
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    private function getClientContractTypesToCheck(Client $client)
    {
        return [
            $client->getClientAndGasContracts(),
            $client->getClientAndEnergyContracts(),
        ];
    }

    public function getContractByNumber(Client $client, $number)
    {
        foreach ($this->getClientContractTypesToCheck($client) as $clientAndContracts) {
            $contract = $this->getContractByNumberFromClientAndContract($clientAndContracts, $number);
            if ($contract) {
                return $contract;
            }
        }

        return null;
    }

    private function getContractByNumberFromClientAndContract($clientAndContracts, $number)
    {
        /** @var ClientAndContractInterface $clientAndContract */
        foreach ($clientAndContracts as $clientAndContract) {
            $contract = $clientAndContract->getContract();
            if (!$contract) {
                continue;
            }

            if ($contract->getContractNumber() == $number) {
                return $contract;
            }
        }

        return null;
    }

    public function calculateMonths(ContractEnergyBase $contractEnergyBase)
    {
        $dateFrom = $contractEnergyBase->getContractFromDate();
        $dateTo = $contractEnergyBase->getContractToDate();
        if (!$dateFrom || !$dateTo) {
            return $contractEnergyBase->getPeriodInMonths();
        }

        $day = (int) $dateTo->format('d');
        if ($day > 1) {
            $dateTo->modify('last day of this month');
        }

        $diff = $dateFrom->diff($dateTo);
        $months = $diff->y * 12 + $diff->m + ($diff->d && $diff->d > 15 ? 1 : 0);

        return $contractEnergyBase->getPeriodInMonths() - $months;
    }

}