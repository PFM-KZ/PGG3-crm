<?php

namespace Wecoders\EnergyBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wecoders\EnergyBundle\Entity\PaymentRequestPackageToGenerate;
use Wecoders\EnergyBundle\Service\PackageToGenerateModel;
use Wecoders\EnergyBundle\Service\PaymentRequestPackageToGenerateModel;

class PaymentRequestRenewGenerate extends Command
{
    private $em;

    private $packageToGenerateModel;

    public function __construct(EntityManager $em, PaymentRequestPackageToGenerateModel $packageToGenerateModel)
    {
        $this->em = $em;
        $this->packageToGenerateModel = $packageToGenerateModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('wecodersenergybundle:payment-request-renew-generate')
            ->setDescription('Renew generate.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->em;
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        /** @var PaymentRequestPackageToGenerate $packageToGenerate */
        $packageToGenerate = $this->packageToGenerateModel->getSingleRecordByStatus(PackageToGenerateModel::STATUS_GENERATE_ERROR);
        if (!$packageToGenerate) {
            dump('No packages with "status generate error" status.');
            die;
        }

        $packageToGenerate->setStatus(PackageToGenerateModel::STATUS_GENERATE);
        $em->persist($packageToGenerate);
        $em->flush($packageToGenerate);
        dump('Status changed to generate');
        dump('Success');
        die;
    }

}