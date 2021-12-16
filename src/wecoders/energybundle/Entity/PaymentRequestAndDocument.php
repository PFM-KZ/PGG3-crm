<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PaymentRequestAndDocument
 *
 * @ORM\Table(name="payment_request_and_document")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\PaymentRequestRepository")
 * @ORM\HasLifecycleCallbacks
 */
class PaymentRequestAndDocument
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Wecoders\EnergyBundle\Entity\PaymentRequest")
     * @ORM\JoinColumn(name="payment_request_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $paymentRequest;

    /**
     * @var string
     *
     * @ORM\Column(name="billing_period", type="string", length=255)
     */
    private $billingPeriod;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="billing_period_from", type="date", nullable=true)
     */
    private $billingPeriodFrom;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="billing_period_to", type="date", nullable=true)
     */
    private $billingPeriodTo;

    /**
     * @var string
     *
     * @ORM\Column(name="days_overdue", type="string", length=255)
     */
    private $daysOverdue;

    /**
     * @var string
     *
     * @ORM\Column(name="document_number", type="string", length=255)
     */
    private $documentNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="to_pay", type="string", length=255)
     */
    private $toPay;

    /**
     * @return string
     */
    public function getBillingPeriod()
    {
        return $this->billingPeriod;
    }

    /**
     * @param string $billingPeriod
     */
    public function setBillingPeriod($billingPeriod)
    {
        $this->billingPeriod = $billingPeriod;
    }

    /**
     * @return \DateTime
     */
    public function getBillingPeriodFrom()
    {
        return $this->billingPeriodFrom;
    }

    /**
     * @param \DateTime $billingPeriodFrom
     */
    public function setBillingPeriodFrom($billingPeriodFrom)
    {
        $this->billingPeriodFrom = $billingPeriodFrom;
    }

    /**
     * @return \DateTime
     */
    public function getBillingPeriodTo()
    {
        return $this->billingPeriodTo;
    }

    /**
     * @param \DateTime $billingPeriodTo
     */
    public function setBillingPeriodTo($billingPeriodTo)
    {
        $this->billingPeriodTo = $billingPeriodTo;
    }

    /**
     * @return string
     */
    public function getDaysOverdue()
    {
        return $this->daysOverdue;
    }

    /**
     * @param string $daysOverdue
     */
    public function setDaysOverdue($daysOverdue)
    {
        $this->daysOverdue = $daysOverdue;
    }

    /**
     * @return string
     */
    public function getDocumentNumber()
    {
        return $this->documentNumber;
    }

    /**
     * @param string $documentNumber
     */
    public function setDocumentNumber($documentNumber)
    {
        $this->documentNumber = $documentNumber;
    }

    /**
     * @return string
     */
    public function getToPay()
    {
        return $this->toPay;
    }

    /**
     * @param string $toPay
     */
    public function setToPay($toPay)
    {
        $this->toPay = $toPay;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getPaymentRequest()
    {
        return $this->paymentRequest;
    }

    /**
     * @param mixed $paymentRequest
     */
    public function setPaymentRequest($paymentRequest)
    {
        $this->paymentRequest = $paymentRequest;
    }
}
