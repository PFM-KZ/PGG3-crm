<?php

namespace GCRM\CRMBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\Service;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateSeparatorDotFromCommaCommand extends Command
{
    /* @var EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('gcrmcrmbundle:update-separator-dot-from-comma')
            ->setDescription('Update tables.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $services = $this->em->getRepository('GCRMCRMBundle:Service')->findAll();
        if ($services) {
            /** @var Service $service */
            foreach ($services as $service) {
                $service->setNetPrice(str_replace(',', '.', $service->getNetPrice()));
                $this->em->persist($service);
                $this->em->flush();
            }
        }

        dump('Success');
    }
}