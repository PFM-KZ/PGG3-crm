<?php

namespace GCRM\CRMBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UpdateCheckUserClientFieldCommand extends Command
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
        $this->setName('gcrmcrmbundle:release-clients-records')
            ->setDescription('Update clients checkUser field.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from('GCRMCRMBundle:Client', 'a')
            ->where('a.checkUser is not null')
            ->getQuery()
        ;

        $clients = $q->getResult();

        /** @var Client $client */
        $now = new \DateTime();

        foreach ($clients as $client) {
            if ($now > $client->getCheckTime()) {
                $client->setCheckTime(null);
                $client->setCheckUser(null);
                $this->em->persist($client);
                $this->em->flush();
            }
        }
    }
}