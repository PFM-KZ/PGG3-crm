<?php

namespace Wecoders\InvoiceBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wecoders\InvoiceBundle\Entity\InvoiceTemplate;

class InsertPredefinedDataCommand extends Command
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('wecodersinvoicebundle:insert-predefined-data')
            ->setDescription('Inserts bundle predefined data.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

    }
}