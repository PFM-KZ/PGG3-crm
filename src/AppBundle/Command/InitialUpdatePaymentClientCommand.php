<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Payment;
use GCRM\CRMBundle\Service\ClientModel;
use GCRM\CRMBundle\Service\PaymentModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitialUpdatePaymentClientCommand extends Command
{
    private $em;
    private $paymentModel;
    private $clientModel;

    public function __construct(EntityManager $em, PaymentModel $paymentModel, ClientModel $clientModel)
    {
        $this->em = $em;
        $this->paymentModel = $paymentModel;
        $this->clientModel = $clientModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('appbundle:initial-update-payment-client')
            ->setDescription('Initial update payment client field based on unique badgeid number.');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $payments = $this->paymentModel->getRecords();
        if (!$payments) {
            dump('No payments found.');
            return;
        }

        /** @var Payment $payment */
        $index = 1;
        foreach ($payments as $payment) {
            $badgeId = $payment->getBadgeId();
            if (!$badgeId) {
                dump('No badge id');
                continue;
            }

            $client = $this->clientModel->getClientByBadgeId($badgeId);
            if (!$client) {
                dump('Client not found by badge id: ' . $badgeId);
                continue;
            }

            $payment->setClient($client);
            $this->em->persist($payment);
            $this->em->flush($payment);

            dump($index);
            $index++;
        }

        dump('Success');
    }

}