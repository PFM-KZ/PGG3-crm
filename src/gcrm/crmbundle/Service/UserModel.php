<?php

namespace GCRM\CRMBundle\Service;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Branch;
use GCRM\CRMBundle\Entity\User;
use GCRM\CRMBundle\Entity\UserAndBranch;

class UserModel
{
    const ENTITY = 'GCRMCRMBundle:User';

    private $branches;
    private $branchesIndexedById;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getBranches(User $user)
    {
        if (!$this->branches) {
            $branches = [];
            $userAndBranches = $user->getUserAndBranches();
            /** @var UserAndBranch $userAndBranch */
            foreach ($userAndBranches as $userAndBranch) {
                $branch = $userAndBranch->getBranch();
                if (!$branch) {
                    continue;
                }
                $branches[] = $branch;
            }

            $this->branches = $branches;
        }

        return $this->branches;
    }

    public function getBranchesIndexedById(User $user)
    {
        if (!$this->branchesIndexedById) {
            $branches = $this->getBranches($user);

            $result = [];
            /** @var Branch $branch */
            foreach ($branches as $branch) {
                $result[$branch->getId()] = $branch;
            }

            $this->branchesIndexedById = $result;
        }

        return $this->branchesIndexedById;
    }

    public function getSalesRepresentativeUsers()
    {
        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from(self::ENTITY, 'a')
            ->where('a.isSalesRepresentative = :isSalesRepresentative')
            ->andWhere('a.proxyNumber IS NOT NULL')
            ->setParameters([
                'isSalesRepresentative' => true,
            ])
            ->getQuery()
        ;

        return $q->getResult();
    }

}