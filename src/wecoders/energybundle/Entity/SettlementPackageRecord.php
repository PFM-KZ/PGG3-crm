<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SettlementPackageRecord
 *
 * @ORM\Table(name="settlement_package_record")
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\PackageToProcessRepository")
 * @ORM\HasLifecycleCallbacks
 */
class SettlementPackageRecord
{
    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\SettlementPackage", inversedBy="settlementPackageRecords")
     * @ORM\JoinColumn(name="settlement_package_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $settlementPackage;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\Client")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $client;

    /**
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param mixed $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @return mixed
     */
    public function getSettlementPackage()
    {
        return $this->settlementPackage;
    }

    /**
     * @param mixed $settlementPackage
     */
    public function setSettlementPackage($settlementPackage)
    {
        $this->settlementPackage = $settlementPackage;
    }

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
     * @ORM\Column(name="pp", type="string", length=255)
     */
    private $pp;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_from", type="datetime", nullable=true)
     */
    private $dateFrom;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_to", type="datetime", nullable=true)
     */
    private $dateTo;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="error_message", type="text", nullable=true)
     */
    private $errorMessage;

    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\InvoiceSettlement", cascade={"remove"})
     * @ORM\JoinColumn(name="invoice_settlement_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $invoiceSettlement;

    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\InvoiceEstimatedSettlement", cascade={"remove"})
     * @ORM\JoinColumn(name="invoice_estimated_settlement_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $invoiceEstimatedSettlement;

    /**
     * @return mixed
     */
    public function getInvoiceSettlement()
    {
        return $this->invoiceSettlement;
    }

    /**
     * @param mixed $invoiceSettlement
     */
    public function setInvoiceSettlement($invoiceSettlement)
    {
        $this->invoiceSettlement = $invoiceSettlement;
    }

    /**
     * @return mixed
     */
    public function getInvoiceEstimatedSettlement()
    {
        return $this->invoiceEstimatedSettlement;
    }

    /**
     * @param mixed $invoiceEstimatedSettlement
     */
    public function setInvoiceEstimatedSettlement($invoiceEstimatedSettlement)
    {
        $this->invoiceEstimatedSettlement = $invoiceEstimatedSettlement;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @param string $errorMessage
     */
    public function setErrorMessage($errorMessage)
    {
        $this->errorMessage = $errorMessage;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
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
     * @return string
     */
    public function getPp()
    {
        return $this->pp;
    }

    /**
     * @param string $pp
     */
    public function setPp($pp)
    {
        $this->pp = $pp;
    }

    /**
     * @return \DateTime
     */
    public function getDateFrom()
    {
        return $this->dateFrom;
    }

    /**
     * @param \DateTime $dateFrom
     */
    public function setDateFrom($dateFrom)
    {
        $this->dateFrom = $dateFrom;
    }

    /**
     * @return \DateTime
     */
    public function getDateTo()
    {
        return $this->dateTo;
    }

    /**
     * @param \DateTime $dateTo
     */
    public function setDateTo($dateTo)
    {
        $this->dateTo = $dateTo;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return SettlementPackageRecord
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
     * @return SettlementPackageRecord
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
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps()
    {
        $this->setUpdatedAt(new \DateTime());

        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new \DateTime());
        }
    }
}

