<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Wecoders\EnergyBundle\Service\SettlementPackageRecordModel;

/**
 * MergeInvoicesPackage
 *
 * @ORM\Table(name="merge_invoices_package")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\MergeInvoicesPackageRepository")
 * @ORM\HasLifecycleCallbacks
 */
class MergeInvoicesPackage
{
    /**
     * @ORM\OneToMany(targetEntity="Wecoders\EnergyBundle\Entity\MergeInvoicesPackageRecord", mappedBy="package", cascade={"remove"})
     */
    private $packageRecords;

    /**
     * @return mixed
     */
    public function getPackageRecords()
    {
        return $this->packageRecords;
    }

    /**
     * @ORM\OneToOne(targetEntity="Wecoders\EnergyBundle\Entity\InvoiceCollective", cascade={"remove"})
     * @ORM\JoinColumn(name="invoice_collective_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $invoice;

    /**
     * @return mixed
     */
    public function getInvoice()
    {
        return $this->invoice;
    }

    /**
     * @param mixed $invoice
     */
    public function setInvoice($invoice)
    {
        $this->invoice = $invoice;
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
     * @var \DateTime
     *
     * @ORM\Column(name="created_date", type="datetime")
     */
    private $createdDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $addedBy;

    /**
     * @return mixed
     */
    public function getAddedBy()
    {
        return $this->addedBy;
    }

    /**
     * @param mixed $addedBy
     */
    public function setAddedBy($addedBy)
    {
        $this->addedBy = $addedBy;
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return MergeInvoicesPackage
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
     * @return \DateTime
     */
    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    /**
     * @param \DateTime $createdDate
     */
    public function setCreatedDate($createdDate)
    {
        $this->createdDate = $createdDate;
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

        if ($this->getCreatedDate() == null) {
            $this->setCreatedDate(new \DateTime());
        }
    }
}

