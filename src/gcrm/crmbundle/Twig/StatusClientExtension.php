<?php

namespace GCRM\CRMBundle\Twig;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\ClientAndContractGas;
use GCRM\CRMBundle\Entity\ContractGas;
use GCRM\CRMBundle\Entity\StatusContractAuthorization;
use GCRM\CRMBundle\Service\StatusClient;
use Twig_Extension;

class StatusClientExtension extends Twig_Extension
{
    /** @var EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('clientStatus', array($this, 'clientStatus')),
        );
    }

    public function clientStatus(Client $client)
    {
        die;
        $status = null;

        /** @var ClientAndContractGas $clientAndGasContract */
        foreach ($client->getClientAndGasContracts() as $clientAndGasContract) {
            /** @var contractGas $contract */
            $contract = $clientAndGasContract->getContract();
            /** @var StatusContractAuthorization $statusAuthorization */
            $statusAuthorization = $contract->getStatusAuthorization();
            $statusAuthorizationCode = $statusAuthorization->getCode();

            if (
                ($statusAuthorizationCode == StatusClient::STATUS_POSITIVE && $status && $status != StatusClient::STATUS_POSITIVE) ||
                ($statusAuthorizationCode == StatusClient::STATUS_NEGATIVE && $status && $status != StatusClient::STATUS_NEGATIVE)
            ) {
                return StatusClient::STATUS_WARNING;
            } elseif ($statusAuthorizationCode == StatusClient::STATUS_POSITIVE) {
                $status = StatusClient::STATUS_POSITIVE;
            } elseif ($statusAuthorizationCode == StatusClient::STATUS_NEGATIVE) {
                $status = StatusClient::STATUS_NEGATIVE;
            }
        }

        return $status;
    }
}