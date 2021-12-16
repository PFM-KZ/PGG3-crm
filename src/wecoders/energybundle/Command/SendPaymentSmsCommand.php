<?php

namespace Wecoders\EnergyBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Wecoders\EnergyBundle\Service\SmsClientGroupModel;

class SendPaymentSmsCommand extends ContainerAwareCommand
{
    /** @var SmsClientGroupModel $sender */
    private $smsClientGroupModel;

    public function __construct(SmsClientGroupModel $smsClientGroupModel)
    {
        $this->smsClientGroupModel = $smsClientGroupModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('wecodersenergybundle:send-payment-sms')
            ->setDescription('Process client group, send SMS messages');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->smsClientGroupModel->processGroup();
    }
}