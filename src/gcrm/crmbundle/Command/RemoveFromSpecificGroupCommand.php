<?php

namespace GCRM\CRMBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Controller\qwe;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Controller\AdminController;
use GCRM\CRMBundle\Entity\Contract;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * this class is responsible for removing clients belonging to selected groups
 * group to choose [gas, energy]
 */
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
            ->addArgument('option', InputArgument::REQUIRED, 'group to remove [gas, energy]')
            ->setDescription('Update contracts checkUser field.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $clients = $this->em->getRepository('GCRMCRMBundle:Client')->findAll();



        /** @var Client $client */
        foreach ($clients as $client) {
            $array = [];
            if ($input->getArgument('option') == 'gas')
            {
                $array = $client->getClientAndGasContracts();
            }
            elseif ($input->getArgument('option') == 'energy')
            {
                $array = $client->getClientAndEnergyContracts();
            }

            if (count($array) > 0)
            {
                $messages = $this->em->getRepository('WecodersEnergyBundle:SmsMessage')->findBy(['client' => $client]);

                foreach ($messages as $message)
                {
                    $this->em->remove($message);
                }

                $this->em->remove($client);
                echo "Usunięty".PHP_EOL;
            }
        }
        $this->em->flush();

    }
}