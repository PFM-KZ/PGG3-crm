<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * StatusContractProcess
 *
 * @ORM\Table(name="status_contract_process")
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\StatusContractVerificationRepository")
 */
class StatusContractProcess
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")`
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\StatusContractAction")
     * @ORM\JoinColumn(name="status_contract_action_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $statusContractAction;

    /**
     * @return mixed
     */
    public function getStatusContractAction()
    {
        return $this->statusContractAction;
    }

    /**
     * @param mixed $statusContractAction
     */
    public function setStatusContractAction($statusContractAction)
    {
        $this->statusContractAction = $statusContractAction;

        return $this;
    }

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\StatusContract")
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
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return StatusContractProcess
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function __toString()
    {
        return $this->title;
    }
}

