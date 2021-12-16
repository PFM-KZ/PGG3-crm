<?php
namespace GCRM\CRMBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 * @ORM\HasLifecycleCallbacks
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="surname", type="string", length=100, nullable=true)
     */
    private $surname;

    /**
     * @var string
     *
     * @ORM\Column(name="telephone", type="string", length=255, nullable=true)
     */
    protected $telephone;

    /**
     * @var string
     *
     * @ORM\Column(name="is_sales_representative", type="boolean", options={"default": 0}, nullable=true)
     */
    protected $isSalesRepresentative;

    /**
     * @var string
     *
     * @ORM\Column(name="proxy_number", type="string", length=100, nullable=true)
     */
    private $proxyNumber;

    /**
     * @return string
     */
    public function getProxyNumber()
    {
        return $this->proxyNumber;
    }

    /**
     * @param string $proxyNumber
     */
    public function setProxyNumber($proxyNumber)
    {
        $this->proxyNumber = $proxyNumber;
    }

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\Branch")
     * @ORM\JoinColumn(name="branch_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $branch;

    /**
     * @ORM\OneToMany(targetEntity="GCRM\CRMBundle\Entity\UserAndBranch", mappedBy="user", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $userAndBranches;

    public function addUserAndBranch(UserAndBranch $userAndBranch)
    {
        $this->userAndBranches[] = $userAndBranch;
        $userAndBranch->setUser($this);

        return $this;
    }

    public function removeUserAndBranch(UserAndBranch $userAndBranch)
    {
        $this->userAndBranches->removeElement($userAndBranch);
    }

    public function getUserAndBranches()
    {
        return $this->userAndBranches;
    }

    public function setUserAndBranches($userAndBranches)
    {
        $this->userAndBranches = $userAndBranches;
    }

    /**
     * @return mixed
     */
    public function getBranch()
    {
        return $this->branch;
    }

    /**
     * @param mixed $branch
     */
    public function setBranch($branch)
    {
        $this->branch = $branch;

        return $this;
    }

    /**
     * Get name
     *
     * @return String
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param String $name
     * @return User
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get surname
     *
     * @return String
     */
    public function getSurname()
    {
        return $this->surname;
    }

    /**
     * Set surname
     *
     * @param String $surname
     * @return User
     */
    public function setSurname($surname)
    {
        $this->surname = $surname;

        return $this;
    }

    /**
     * @return string
     */
    public function getIsSalesRepresentative()
    {
        return $this->isSalesRepresentative;
    }

    /**
     * @param string $isSalesRepresentative
     */
    public function setIsSalesRepresentative($isSalesRepresentative)
    {
        $this->isSalesRepresentative = $isSalesRepresentative;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return User
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return User
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Get telephone
     *
     * @return String
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * Set telephone
     *
     * @param String $about
     * @return User
     */
    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;

        return $this;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps()
    {
        $this->setUpdatedAt(new \DateTime('now'));

        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new \DateTime('now'));
        }
    }
}