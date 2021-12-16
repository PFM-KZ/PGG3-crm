<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ContractEnergyAndDistributionTariff
 *
 * @ORM\Table(name="contract_energy_and_distribution_tariff")
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\ContractRepositoryRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ContractEnergyAndDistributionTariff
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
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\ContractEnergy", inversedBy="contractAndDistributionTariffs")
     * @ORM\JoinColumn(name="contract_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $contract;

    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\Tariff")
     * @ORM\JoinColumn(name="tariff_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $tariff;

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
    public function getTariff()
    {
        return $this->tariff;
    }

    /**
     * @param mixed $tariff
     */
    public function setTariff($tariff)
    {
        $this->tariff = $tariff;
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

