<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * StatusContract
 *
 * @ORM\Table(name="status_contract")
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\StatusContractRepository")
 */
class StatusContract
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
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var integer
     *
     * @ORM\Column(name="special_action_option", type="integer", nullable=true)
     */
    private $specialActionOption;

    /**
     * @return int
     */
    public function getSpecialActionOption()
    {
        return $this->specialActionOption;
    }

    /**
     * @param int $specialActionOption
     */
    public function setSpecialActionOption($specialActionOption)
    {
        $this->specialActionOption = $specialActionOption;
    }

    /**
     * @ORM\OneToMany(targetEntity="GCRM\CRMBundle\Entity\StatusContractAndSpecialAction", mappedBy="statusContract", cascade={"persist","remove"}, orphanRemoval=true)
     */
    private $specialActions;

    public function addSpecialAction(StatusContractAndSpecialAction $specialAction)
    {
        $this->specialActions[] = $specialAction;
        $specialAction->setStatusContract($this);

        return $this;
    }

    public function removeSpecialAction(StatusContractAndSpecialAction $specialAction)
    {
        $this->specialActions->removeElement($specialAction);
    }

    public function getSpecialActions()
    {
        return $this->specialActions;
    }

    public function setSpecialActions($specialActions)
    {
        $this->specialActions = $specialActions;
    }

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
     * @return StatusContract
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

