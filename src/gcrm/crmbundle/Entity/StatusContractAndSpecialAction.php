<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * StatusContractAndSpecialAction
 *
 * @ORM\Table(name="status_contract_and_special_action")
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\StatusContractRepository")
 * @ORM\HasLifecycleCallbacks
 */
class StatusContractAndSpecialAction
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
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\StatusContract", inversedBy="specialActions")
     * @ORM\JoinColumn(name="status_contract_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $statusContract;

    /**
     * @return mixed
     */
    public function getStatusContract()
    {
        return $this->statusContract;
    }

    /**
     * @param mixed $statusContract
     */
    public function setStatusContract($statusContract)
    {
        $this->statusContract = $statusContract;
    }

    /**
     * @var integer
     *
     * @ORM\Column(name="option", type="integer", nullable=true)
     */
    private $option;

    /**
     * @return int
     */
    public function getOption()
    {
        return $this->option;
    }

    /**
     * @param int $option
     */
    public function setOption($option)
    {
        $this->option = $option;
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }


    public function __toString()
    {
        return (string) $this->id;
    }

}

