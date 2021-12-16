<?php

namespace Wecoders\EnergyBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Wecoders\EnergyBundle\Service\SmsClientGroupModel;

class CreatePaymentGroups extends ContainerAwareCommand
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
            ->setName('wecodersenergybundle:create-payment-groups')
            ->setDescription('Creates payments group to send SMS messages to');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->smsClientGroupModel->createPrePaymentGroup();
        $this->smsClientGroupModel->createPostPaymentGroup();
    }
}
