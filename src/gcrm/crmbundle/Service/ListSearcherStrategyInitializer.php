<?php

namespace GCRM\CRMBundle\Service;

use GCRM\CRMBundle\Service\ListSearcher\Payment;
use GCRM\CRMBundle\Service\ListSearcher\Invoice;
use Symfony\Component\HttpFoundation\RequestStack;
use GCRM\CRMBundle\Service\ListSearcher\ChangeStatusLog;
use GCRM\CRMBundle\Service\ListSearcher\InvoiceProforma;
use GCRM\CRMBundle\Service\ListSearcher\InvoiceCorrection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TZiebura\CorrespondenceBundle\Service\ListSearcher\Thread;
use Wecoders\EnergyBundle\Service\ListSearcher\Client;
use Wecoders\EnergyBundle\Service\ListSearcher\DebitNote;
use Wecoders\EnergyBundle\Service\ListSearcher\InvoiceCollective;
use Wecoders\EnergyBundle\Service\ListSearcher\ClientEnquiry;
use Wecoders\EnergyBundle\Service\ListSearcher\InvoiceCorrectionEnergy;
use Wecoders\EnergyBundle\Service\ListSearcher\InvoiceEnergy;
use Wecoders\EnergyBundle\Service\ListSearcher\InvoiceEstimatedSettlementCorrectionEnergy;
use Wecoders\EnergyBundle\Service\ListSearcher\InvoiceEstimatedSettlementEnergy;
use Wecoders\EnergyBundle\Service\ListSearcher\PaymentRequest;
use GCRM\CRMBundle\Service\ListSearcher\EntityListSearcherInterface;
use Wecoders\EnergyBundle\Service\ListSearcher\InvoiceProformaEnergy;
use Wecoders\EnergyBundle\Service\ListSearcher\Client as ClientEnergy;
use Wecoders\EnergyBundle\Service\ListSearcher\InvoiceSettlementEnergy;
use Wecoders\EnergyBundle\Service\ListSearcher\InvoiceProformaCorrectionEnergy;
use Wecoders\EnergyBundle\Service\ListSearcher\InvoiceSettlementCorrectionEnergy;
use Wecoders\EnergyBundle\Service\ListSearcher\SmsMessage;

class ListSearcherStrategyInitializer
{
    private $objects = [];

    /**
     * @return array
     */
    public function getObjects()
    {
        return $this->objects;
    }

    /**
     * @param array $objects
     */
    public function setObjects($objects)
    {
        $this->objects = $objects;
    }

    public function __construct(RequestStack $requestStack, ContainerInterface $container)
    {
        $request = $requestStack->getCurrentRequest();
        $this->objects = [
            new Invoice($request, $container),
            new InvoiceProforma($request, $container),
            new InvoiceCorrection($request, $container),
            new ChangeStatusLog($request, $container),
            new Payment($request, $container),
            new ClientEnergy($request, $container),
            new InvoiceProformaEnergy($request, $container),
            new InvoiceProformaCorrectionEnergy($request, $container),
            new InvoiceSettlementEnergy($request, $container),
            new InvoiceSettlementCorrectionEnergy($request, $container),
            new InvoiceEstimatedSettlementEnergy($request, $container),
            new InvoiceEstimatedSettlementCorrectionEnergy($request, $container),
            new PaymentRequest($request, $container),
            new Thread($request, $container),
            new SmsMessage($request, $container),
            new DebitNote($request, $container),
            new InvoiceEnergy($request, $container),
            new InvoiceCorrectionEnergy($request, $container),
            new InvoiceCollective($request, $container),
            new ClientEnquiry($request, $container),
        ];
    }

    public function chooseObjectByEntity($entity)
    {
        /** @var EntityListSearcherInterface $object */
        foreach ($this->objects as $object) {
            if ($object->getEntity() == $entity) {
                return $object;
            }
        }
        return null;
    }
}