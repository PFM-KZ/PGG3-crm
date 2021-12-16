<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserAndBranch
 *
 * @ORM\Table(name="user_and_branch")
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\UserAndBranchRepository")
 * @ORM\HasLifecycleCallbacks
 */
class UserAndBranch
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\User", inversedBy="userAndBranches")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\Branch")
     * @ORM\JoinColumn(name="branch_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $branch;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    public function getBranch()
    {
        return $this->branch;
    }

    public function setBranch(Branch $branch)
    {
        $this->branch = $branch;

        return $this;
    }

    public function __toString()
    {
        return $this->branch ? $this->branch->getTitle() : '---';
    }
}

