<?php

namespace GCRM\CRMBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Contract;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UpdateCheckUserContractFieldCommand extends Command
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
        $this->setName('gcrmcrmbundle:release-contracts-records')
            ->setDescription('Update contracts checkUser field.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from('GCRMCRMBundle:Contract', 'a')
            ->where('a.checkUser is not null')
            ->getQuery()
        ;

        $contracts = $q->getResult();

        /** @var Contract $contract */
        $now = new \DateTime();

        foreach ($contracts as $contract) {
            if ($now > $contract->getCheckTime()) {
                $contract->setCheckTime(null);
                $contract->setCheckUser(null);
                $this->em->persist($contract);
                $this->em->flush();
            }
        }
    }
}