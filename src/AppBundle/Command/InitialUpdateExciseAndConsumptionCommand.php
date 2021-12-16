<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wecoders\EnergyBundle\Entity\InvoiceInterface;
use Wecoders\EnergyBundle\Service\ContractModel;
use Wecoders\EnergyBundle\Service\ExciseModel;

class InitialUpdateExciseAndConsumptionCommand extends Command
{
    private $em;
    private $contractModel;
    private $exciseModel;

    public function __construct(EntityManager $em, ContractModel $contractModel, ExciseModel $exciseModel)
    {
        $this->em = $em;
        $this->contractModel = $contractModel;
        $this->exciseModel = $exciseModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('appbundle:initial-update-excise-and-consumption')
            ->addArgument('entityName', InputArgument::REQUIRED, 'Give full entity name')
            ->addArgument('recalculate', InputArgument::REQUIRED, 'Recalculate (default)')
            ->addArgument('recalculateConsumption', InputArgument::REQUIRED, 'Recalculate consumption')
            ->addArgument('recalculateExcise', InputArgument::REQUIRED, 'Recalculate excise')
            ->addArgument('recalculateAll', InputArgument::REQUIRED, 'Recalculate all')
            ->setDescription('Initial update excise and consumption.');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $invoices = $this->em->getRepository($input->getArgument('entityName'))->findAll();
        $errors = [];
        /** @var InvoiceInterface $invoice */
        $index = 1;
        foreach ($invoices as $invoice) {
            // ommit records that are already setup
            if ($input->getArgument('recalculate') && ($invoice->getConsumption() || is_numeric($invoice->getConsumption()))) {
                dump('consumption already exist');
                continue;
            }

            $recalculated = '';
            if ($input->getArgument('recalculate') || $input->getArgument('recalculateConsumption') || $input->getArgument('recalculateAll')) {
                $invoice->recalculateConsumption();
                $recalculated = ' consumption';
            }

            if ($input->getArgument('recalculate') || $input->getArgument('recalculateExcise') || $input->getArgument('recalculateAll')) {
                if ($invoice->getType() == 'ENERGY') {
                    if (!$invoice->getExcise()) {
                        $exciseValue = $this->exciseModel->getExciseValueByDate($invoice->getBillingPeriodFrom());
                        $invoice->setExcise($exciseValue);
                    }
                    $invoice->recalculateExciseValue();
                } else {
                    $invoice->setExcise(0);
                    $invoice->setExciseValue(0);
                }

                $recalculated .= ' excise';
            }


            if ($recalculated) {
                $this->em->persist($invoice);
                $this->em->flush($invoice);

                $index++;
                dump($index . $recalculated);
            } else {
                dump('nothing to recalculate');
            }
        }
        dump($errors);

        dump('Success');
    }

}