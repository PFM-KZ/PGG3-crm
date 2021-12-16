<?php

namespace Wecoders\EnergyBundle\Service;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\ContractEnergy;
use GCRM\CRMBundle\Entity\ContractGas;

/**
 * Access contract object data by given contract number
 */
class ContractAccessor
{
    const KEY_DATA_CONTRACT = 'contract';

    private $em;
    private $contractModel;

    public function __construct(EntityManager $em, \GCRM\CRMBundle\Service\ContractModel $contractModel)
    {
        $this->em = $em;
        $this->contractModel = $contractModel;
    }

    /**
     * Appends contract object to given object by contract number
     * Usage example: Object does not have reference to contract, only contract number as string but needs data from it.
     */
    public function append($contractNumber, $object)
    {
        $contract = $this->contractModel->getContractByNumber($contractNumber);
        $object->dataAppended[self::KEY_DATA_CONTRACT] = $contract;
        return $object;
    }

    public function fetchContract($contractNumber)
    {
        $contract = $this->contractModel->getContractByNumber($contractNumber);
        if ($contract) {
            return $contract;
        }

        return null;
    }

    public function fetchClientAndContract($contractNumber, Client $client)
    {
        $clientAndContract = $this->contractModel->getClientAndContractBy('contractNumber', $contractNumber, $client);
        return $clientAndContract;
    }

    public function access($contractNumber, $method)
    {
        $contract = $this->contractModel->getContractByNumber($contractNumber);
        if ($contract) {
            return $contract->{$method}();
        }

        return null;
    }

    public function accessBy($property, $value, $method)
    {
        $contract = $this->contractModel->getContractBy($property, $value);
        if ($contract) {
            return $contract->{$method}();
        }

        return null;
    }

    public function accessClientAndContractBy($property, $value, $searchIn = 'contract', $fromTables = null)
    {
        $clientAndContract = $this->contractModel->getClientAndContractBy($property, $value, null, $searchIn, $fromTables);
        if ($clientAndContract) {
            return $clientAndContract;
        }

        return null;
    }

    public function accessContractBy($property, $value, $searchIn = 'contract', $fromTables = null)
    {
        $clientAndContract = $this->contractModel->getClientAndContractBy($property, $value, null, $searchIn, $fromTables);
        if ($clientAndContract) {
            return $clientAndContract->getContract();
        }

        return null;
    }

    public function accessContractPriceList($contractNumber, $date)
    {
        $clientAndContract = $this->contractModel->getClientAndContractBy('contractNumber', $contractNumber, null);
        if ($clientAndContract) {
            $contract = $clientAndContract->getContract();
            if ($contract) {
                return $contract->getPriceListByDate($date);
            }
        }

        return null;
    }

    public function manageContractByType(Client $client, $type)
    {
        if ($type == ContractGas::TYPE) {
            $clientAndContracts = $client->getClientAndGasContracts();
        } elseif ($type == ContractEnergy::TYPE) {
            $clientAndContracts = $client->getClientAndEnergyContracts();
        } else {
            throw new \RuntimeException('This contract type not exist');
        }

        foreach ($clientAndContracts as $clientAndContract) {
            $contract = $clientAndContract->getContract();
            if ($contract) {
                return $contract;
            }
        }

        return null;
    }

}