<?php

namespace GCRM\CRMBundle\Event;

use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\Payment;
use GCRM\CRMBundle\Service\Balance;
use GCRM\CRMBundle\Service\BillingDocument\Initializer;
use GCRM\CRMBundle\Service\ClientModel;
use GCRM\CRMBundle\Service\InvoiceModel;
use GCRM\CRMBundle\Service\PaymentModel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PaymentsUploadedSubscriber implements EventSubscriberInterface
{
    private $clientModel;

    private $invoiceModel;

    private $paymentModel;

    private $container;

    private $initializer;

    public function __construct(ClientModel $clientModel, InvoiceModel $invoiceModel, PaymentModel $paymentModel, ContainerInterface $container, Initializer $initializer)
    {
        $this->clientModel = $clientModel;
        $this->invoiceModel = $invoiceModel;
        $this->paymentModel = $paymentModel;
        $this->container = $container;
        $this->initializer = $initializer;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'payments.uploaded' => [
                ['updateInvoices'],
                ['updatePaymentRequests'],
            ]
        ];
    }

    /**
     * @param PaymentsUploadedEvent $event
     */
    public function updateInvoices(PaymentsUploadedEvent $event)
    {
        $payments = $event->getPayments();

        // update invoices / corrections to those clients
        $clientsToUpdate = [];
        $tmpIds = [];

        /** @var Payment $payment */
        foreach ($payments as $payment) {

            /** @var Client $client */
            $client = $this->clientModel->getClientByBadgeId($payment->getBadgeId());
            if (!$client) {
                continue;
            }

            if (!in_array($client->getId(), $tmpIds)) {
                $clientsToUpdate[] = $client;
                $tmpIds[] = $client->getId();
            }
        }


        // make an update
        /** @var Client $client */
        foreach ($clientsToUpdate as $client) {
            $billingDocumentsObject = $this->initializer->init($client)->generate();
            $billingDocumentsObject->updateDocumentsIsPaidState();
        }
    }

    /**
     * @param PaymentsUploadedEvent $event
     */
    public function updatePaymentRequests(PaymentsUploadedEvent $event)
    {
        $kernelRootDir = $this->container->get('kernel')->getRootDir();
        shell_exec('php ' . $kernelRootDir . '/../bin/console wecodersenergybundle:payment-request-update-is-paid-state');
    }
}