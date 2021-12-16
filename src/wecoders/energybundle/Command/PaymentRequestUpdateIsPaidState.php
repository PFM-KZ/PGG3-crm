<?php

namespace Wecoders\EnergyBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Service\PaymentModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wecoders\EnergyBundle\Entity\PaymentRequest;
use Wecoders\EnergyBundle\Service\PaymentRequestModel;

class PaymentRequestUpdateIsPaidState extends Command
{
    private $em;

    private $paymentModel;

    private $paymentRequestModel;

    public function __construct(EntityManager $em, PaymentRequestModel $paymentRequestModel, PaymentModel $paymentModel)
    {
        $this->em = $em;
        $this->paymentModel = $paymentModel;
        $this->paymentRequestModel = $paymentRequestModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('wecodersenergybundle:payment-request-update-is-paid-state')
            ->setDescription('Process package.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->em;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $notPaid = $this->paymentRequestModel->getRecordsNotPaid();
        if (!$notPaid) {
            dump('No not paid records');
        }

        /** @var PaymentRequest $paymentRequest */
        $index = 1;
        foreach ($notPaid as $paymentRequest) {
            /** @var Client $client */
            $client = $paymentRequest->getClient();
            if (!$client) {
                dump('Record does not have client');
                continue;
            }

            $summaryValueToPay = $paymentRequest->getSummaryGrossValue();
            $paidValueFromPaymentRequestCreatedDate = $this->paymentModel->getPaymentsSummaryValueByNumberFromDate($client->getAccountNumberIdentifier()->getNumber(), $paymentRequest->getCreatedDate());

            if ((string) $paidValueFromPaymentRequestCreatedDate >= (string) $summaryValueToPay) {
                $paymentRequest->setIsPaid(true);
                $this->em->persist($paymentRequest);
                $this->em->flush($paymentRequest);
                dump('Changed state: ' . $paymentRequest->getId());
            }

            dump($index);
            $index++;
        }

        dump('Success');
    }

}