<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * InvoiceAndRabate
 *
 * @ORM\Table(name="invoice_and_rabate")
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\InvoiceAndRabateRepository")
 * @ORM\HasLifecycleCallbacks
 */
class InvoiceAndRabate
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
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\Invoices", inversedBy="invoiceAndRabates")
     * @ORM\JoinColumn(name="invoice_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $invoice;

    /**
     * @ORM\ManyToOne(targetEntity="GCRM\CRMBundle\Entity\Rabate")
     * @ORM\JoinColumn(name="rabate_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $rabate;

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
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getInvoice()
    {
        return $this->invoice;
    }

    public function setInvoice(Invoices $invoice)
    {
        $this->invoice = $invoice;
    }

    public function getRabate()
    {
        return $this->rabate;
    }

    public function setRabate(Rabate $rabate)
    {
        $this->rabate = $rabate;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return InvoiceAndRabate
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
     * @return InvoiceAndRabate
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
        $this->setUpdatedAt(new \DateTime('now'));

        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new \DateTime('now'));
        }
    }

    public function __toString()
    {
        return $this->rabate->getTitle();
    }
}

