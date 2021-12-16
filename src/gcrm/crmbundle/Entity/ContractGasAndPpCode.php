<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ContractGasAndPpCode
 *
 * @ORM\Table(name="contract_gas_and_pp_code")
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\ContractRepositoryRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ContractGasAndPpCode
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
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\ContractGas", inversedBy="contractAndPpCodes")
     * @ORM\JoinColumn(name="contract_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $contract;

    /**
     * @var string
     *
     * @ORM\Column(name="ppe_code", type="string", nullable=true)
     */
    private $ppCode;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="from_date", type="datetime", nullable=true)
     */
    private $fromDate;

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
     * @return mixed
     */
    public function getContract()
    {
        return $this->contract;
    }

    /**
     * @param mixed $contract
     */
    public function setContract($contract)
    {
        $this->contract = $contract;
    }

    /**
     * @return mixed
     */
    public function getPpCode()
    {
        return $this->ppCode;
    }

    /**
     * @param mixed $ppCode
     */
    public function setPpCode($ppCode)
    {
        $this->ppCode = $ppCode;
    }

    /**
     * @return \DateTime
     */
    public function getFromDate()
    {
        return $this->fromDate;
    }

    /**
     * @param \DateTime $fromDate
     */
    public function setFromDate($fromDate)
    {
        $this->fromDate = $fromDate;
    }

}

