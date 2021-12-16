<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MergeInvoicesPackageRecord
 *
 * @ORM\Table(name="merge_invoices_package_record")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\MergeInvoicesPackageRecordRepository")
 * @ORM\HasLifecycleCallbacks
 */
class MergeInvoicesPackageRecord
{
    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\MergeInvoicesPackage", inversedBy="packageRecords")
     * @ORM\JoinColumn(name="package_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $package;

    /**
     * @return mixed
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * @param mixed $package
     */
    public function setPackage($package)
    {
        $this->package = $package;
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
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\InvoiceSettlement")
     * @ORM\JoinColumn(name="invoice_settlement_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $invoiceSettlement;

    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\InvoiceEstimatedSettlement")
     * @ORM\JoinColumn(name="invoice_estimated_settlement_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $invoiceEstimatedSettlement;

    public function getInvoice()
    {
        if ($this->invoiceSettlement) {
            return $this->invoiceSettlement;
        } elseif ($this->invoiceEstimatedSettlement) {
            return $this->invoiceEstimatedSettlement;
        }

        return null;
    }

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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return MergeInvoicesPackageRecord
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
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps()
    {
        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new \DateTime());
        }
    }
}

