<?php

namespace GCRM\CRMBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Controller\qwe;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Controller\AdminController;
use GCRM\CRMBundle\Entity\Contract;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RemoveFromSpecificGroupCommand extends Command
{
    /* @var EntityManager $em */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('gcrmcrmbundle:remove-from-group')
            ->setDescription('Update contracts checkUser field.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $clients = $this->em->getRepository('GCRMCRMBundle:Client')->findAll();


        /** @var Client $client */
        foreach ($clients as $client) {

            if (count($client->getClientAndEnergyContracts()) > 0)
            {
                $messages = $this->em->getRepository('WecodersEnergyBundle:SmsMessage')->findBy(['client' => $client]);

                foreach ($messages as $message)
                {
                    $this->em->remove($message);
                    $this->em->flush();
                }

                $this->em->remove($client);
                $this->em->flush();
                echo "Usunięty".PHP_EOL;
            }
        }
    }
}