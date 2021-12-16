<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * StatusClient
 *
 * @ORM\Table(name="status_client_verification")
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\StatusClientVerificationRepository")
 */
class StatusClientVerification
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
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=100)
     */
    private $code;

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
     * @return StatusClientVerification
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

    /**
     * Set code
     *
     * @param string $code
     *
     * @return StatusClientVerification
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    public function __toString()
    {
        return $this->title;
    }
}

