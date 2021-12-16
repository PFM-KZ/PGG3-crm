<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ChargesGroup
 *
 * @ORM\Table(name="energy_data")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\EnergyDataRepository")
 * @ORM\HasLifecycleCallbacks
 */
class EnergyData
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
     * PPE / PPG
     *
     * @ORM\Column(name="pp_code", type="string", length=255)
     */
    private $ppCode;

    /**
     * @var string
     *
     * Id Gas / Energy device
     *
     * @ORM\Column(name="device_id", type="string", length=255, nullable=true)
     */
    private $deviceId;

    /**
     * @var string
     *
     * Serial number Gas / Energy device
     *
     * @ORM\Column(name="device_serial_number", type="string", length=255, nullable=true)
     */
    private $deviceSerialNumber;

    /**
     * @var string
     *
     * Id of single record reading
     *
     * @ORM\Column(name="reading_id", type="string", length=255, nullable=true)
     */
    private $readingId;

    /**
     * @var string
     *
     * Area of reading
     *
     * @ORM\Column(name="area", type="string", length=255, nullable=true)
     */
    private $area;

    /**
     * @var string
     *
     * Area of reading - original version
     *
     * @ORM\Column(name="area_original", type="string", length=255, nullable=true)
     */
    private $areaOriginal;

    /**
     * @var string
     *
     * @ORM\Column(name="seller", type="string", length=255, nullable=true)
     */
    private $seller;

    /**
     * @var string
     *
     * W.. / G..
     *
     * @ORM\Column(name="tariff", type="string", length=255)
     */
    private $tariff;


    /**
     * @var string
     *
     * @ORM\Column(name="client_name", type="string", length=255, nullable=true)
     */
    private $clientName;

    /**
     * @var string
     *
     * @ORM\Column(name="client_address", type="string", length=255, nullable=true)
     */
    private $clientAddress;

    /**
     * @var string
     *
     * @ORM\Column(name="device", type="string", length=255, nullable=true)
     */
    private $device;

    /**
     * @var string
     *
     * @ORM\Column(name="state_start", type="string", length=255)
     */
    private $stateStart;

    private $calculatedStateStart;

    /**
     * @var string
     *
     * @ORM\Column(name="state_end", type="string", length=255)
     */
    private $stateEnd;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="billing_period_from", type="datetime", nullable=true)
     */
    private $billingPeriodFrom;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="billing_period_to", type="datetime", nullable=true)
     */
    private $billingPeriodTo;

    /**
     * @var string
     *
     * @ORM\Column(name="date_of_reading", type="datetime", nullable=true)
     */
    private $dateOfReading;

    /**
     * @var string
     *
     * @ORM\Column(name="consumption_m", type="string", length=255, nullable=true)
     */
    private $consumptionM;

    private $calculatedConsumptionM;

    /**
     * @var string
     *
     * @ORM\Column(name="consumption_kwh", type="string", length=255)
     */
    private $consumptionKwh;

    private $calculatedConsumptionKwh;

    /**
     * @var string
     *
     * m3 to kWh ratio
     *
     * @ORM\Column(name="ratio", type="string", length=255)
     */
    private $ratio;

    /**
     * @var string
     *
     * EE / GS
     *
     * @ORM\Column(name="energy_type", type="string", length=255)
     */
    private $energyType;

    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="string", length=255)
     */
    private $filename;

    /**
     * @var string
     *
     * UDPM / UDPP etc.
     *
     * @ORM\Column(name="fileType", type="string", length=255, nullable=true)
     */
    private $fileType;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255)
     */
    private $code;

    /**
     * @var string
     *
     * R / S / O
     *
     * @ORM\Column(name="reading_type", type="string", length=10, nullable=true)
     */
    private $readingType;

    /**
     * @var string
     *
     * R / S / O - meaning but differently writen
     *
     * @ORM\Column(name="reading_type_original", type="string", length=100, nullable=true)
     */
    private $readingTypeOriginal;

    /**
     * @var string
     *
     * Tauron additional status
     *
     * @ORM\Column(name="reading_status", type="string", length=20, nullable=true)
     */
    private $readingStatus;

    /**
     * @var string
     *
     * Tauron additional status
     *
     * @ORM\Column(name="billing_status", type="string", length=20, nullable=true)
     */
    private $billingStatus;




    /**
     * @var string
     *
     * @ORM\Column(name="consumption_split_all_day", type="string", length=15, nullable=true)
     */
    private $consumptionSplitAllDay;

    /**
     * @var string
     *
     * @ORM\Column(name="consumption_split_peak", type="string", length=15, nullable=true)
     */
    private $consumptionSplitPeak;

    /**
     * @var string
     *
     * @ORM\Column(name="consumption_split_off_peak", type="string", length=15, nullable=true)
     */
    private $consumptionSplitOffPeak;

    /**
     * @var string
     *
     * @ORM\Column(name="consumption_split_day", type="string", length=15, nullable=true)
     */
    private $consumptionSplitDay;

    /**
     * @var string
     *
     * @ORM\Column(name="consumption_split_night", type="string", length=15, nullable=true)
     */
    private $consumptionSplitNight;

    /**
     * @var string
     *
     * @ORM\Column(name="consumption_split_morning_peak", type="string", length=15, nullable=true)
     */
    private $consumptionSplitMorningPeak;

    /**
     * @var string
     *
     * @ORM\Column(name="consumption_split_afternoon_peak", type="string", length=15, nullable=true)
     */
    private $consumptionSplitAfternoonPeak;

    /**
     * @var string
     *
     * @ORM\Column(name="consumption_split_remaining_hours_of_day", type="string", length=15, nullable=true)
     */
    private $consumptionSplitRemainingHoursOfDay;

    /**
     * @var string
     *
     * @ORM\Column(name="state_start_split_first_part", type="string", length=15, nullable=true)
     */
    private $stateStartSplitFirstPart;

    /**
     * @var string
     *
     * @ORM\Column(name="state_start_split_second_part", type="string", length=15, nullable=true)
     */
    private $stateStartSplitSecondPart;

    /**
     * @var string
     *
     * @ORM\Column(name="state_start_split_third_part", type="string", length=15, nullable=true)
     */
    private $stateStartSplitThirdPart;

    /**
     * @var string
     *
     * @ORM\Column(name="state_end_split_first_part", type="string", length=15, nullable=true)
     */
    private $stateEndSplitFirstPart;

    /**
     * @var string
     *
     * @ORM\Column(name="state_end_split_second_part", type="string", length=15, nullable=true)
     */
    private $stateEndSplitSecondPart;

    /**
     * @var string
     *
     * @ORM\Column(name="state_end_split_third_part", type="string", length=15, nullable=true)
     */
    private $stateEndSplitThirdPart;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return string
     */
    public function getStateStartSplitFirstPart()
    {
        return $this->stateStartSplitFirstPart;
    }

    /**
     * @param string $stateStartSplitFirstPart
     */
    public function setStateStartSplitFirstPart($stateStartSplitFirstPart)
    {
        $this->stateStartSplitFirstPart = $stateStartSplitFirstPart;
    }

    /**
     * @return string
     */
    public function getStateStartSplitSecondPart()
    {
        return $this->stateStartSplitSecondPart;
    }

    /**
     * @param string $stateStartSplitSecondPart
     */
    public function setStateStartSplitSecondPart($stateStartSplitSecondPart)
    {
        $this->stateStartSplitSecondPart = $stateStartSplitSecondPart;
    }

    /**
     * @return string
     */
    public function getStateStartSplitThirdPart()
    {
        return $this->stateStartSplitThirdPart;
    }

    /**
     * @param string $stateStartSplitThirdPart
     */
    public function setStateStartSplitThirdPart($stateStartSplitThirdPart)
    {
        $this->stateStartSplitThirdPart = $stateStartSplitThirdPart;
    }

    /**
     * @return string
     */
    public function getStateEndSplitFirstPart()
    {
        return $this->stateEndSplitFirstPart;
    }

    /**
     * @param string $stateEndSplitFirstPart
     */
    public function setStateEndSplitFirstPart($stateEndSplitFirstPart)
    {
        $this->stateEndSplitFirstPart = $stateEndSplitFirstPart;
    }

    /**
     * @return string
     */
    public function getStateEndSplitSecondPart()
    {
        return $this->stateEndSplitSecondPart;
    }

    /**
     * @param string $stateEndSplitSecondPart
     */
    public function setStateEndSplitSecondPart($stateEndSplitSecondPart)
    {
        $this->stateEndSplitSecondPart = $stateEndSplitSecondPart;
    }

    /**
     * @return string
     */
    public function getStateEndSplitThirdPart()
    {
        return $this->stateEndSplitThirdPart;
    }

    /**
     * @param string $stateEndSplitThirdPart
     */
    public function setStateEndSplitThirdPart($stateEndSplitThirdPart)
    {
        $this->stateEndSplitThirdPart = $stateEndSplitThirdPart;
    }

    /**
     * @return string
     */
    public function getConsumptionSplitAllDay()
    {
        return $this->consumptionSplitAllDay;
    }

    /**
     * @param string $consumptionSplitAllDay
     */
    public function setConsumptionSplitAllDay($consumptionSplitAllDay)
    {
        $this->consumptionSplitAllDay = $consumptionSplitAllDay;
    }

    /**
     * @return string
     */
    public function getConsumptionSplitPeak()
    {
        return $this->consumptionSplitPeak;
    }

    /**
     * @param string $consumptionSplitPeak
     */
    public function setConsumptionSplitPeak($consumptionSplitPeak)
    {
        $this->consumptionSplitPeak = $consumptionSplitPeak;
    }

    /**
     * @return string
     */
    public function getConsumptionSplitOffPeak()
    {
        return $this->consumptionSplitOffPeak;
    }

    /**
     * @param string $consumptionSplitOffPeak
     */
    public function setConsumptionSplitOffPeak($consumptionSplitOffPeak)
    {
        $this->consumptionSplitOffPeak = $consumptionSplitOffPeak;
    }

    /**
     * @return string
     */
    public function getConsumptionSplitDay()
    {
        return $this->consumptionSplitDay;
    }

    /**
     * @param string $consumptionSplitDay
     */
    public function setConsumptionSplitDay($consumptionSplitDay)
    {
        $this->consumptionSplitDay = $consumptionSplitDay;
    }

    /**
     * @return string
     */
    public function getConsumptionSplitNight()
    {
        return $this->consumptionSplitNight;
    }

    /**
     * @param string $consumptionSplitNight
     */
    public function setConsumptionSplitNight($consumptionSplitNight)
    {
        $this->consumptionSplitNight = $consumptionSplitNight;
    }

    /**
     * @return string
     */
    public function getConsumptionSplitMorningPeak()
    {
        return $this->consumptionSplitMorningPeak;
    }

    /**
     * @param string $consumptionSplitMorningPeak
     */
    public function setConsumptionSplitMorningPeak($consumptionSplitMorningPeak)
    {
        $this->consumptionSplitMorningPeak = $consumptionSplitMorningPeak;
    }

    /**
     * @return string
     */
    public function getConsumptionSplitAfternoonPeak()
    {
        return $this->consumptionSplitAfternoonPeak;
    }

    /**
     * @param string $consumptionSplitAfternoonPeak
     */
    public function setConsumptionSplitAfternoonPeak($consumptionSplitAfternoonPeak)
    {
        $this->consumptionSplitAfternoonPeak = $consumptionSplitAfternoonPeak;
    }

    /**
     * @return string
     */
    public function getConsumptionSplitRemainingHoursOfDay()
    {
        return $this->consumptionSplitRemainingHoursOfDay;
    }

    /**
     * @param string $consumptionSplitRemainingHoursOfDay
     */
    public function setConsumptionSplitRemainingHoursOfDay($consumptionSplitRemainingHoursOfDay)
    {
        $this->consumptionSplitRemainingHoursOfDay = $consumptionSplitRemainingHoursOfDay;
    }



    /**
     * @return string
     */
    public function getReadingStatus()
    {
        return $this->readingStatus;
    }

    /**
     * @param string $readingStatus
     */
    public function setReadingStatus($readingStatus)
    {
        $this->readingStatus = $readingStatus;
    }

    /**
     * @return string
     */
    public function getBillingStatus()
    {
        return $this->billingStatus;
    }

    /**
     * @param string $billingStatus
     */
    public function setBillingStatus($billingStatus)
    {
        $this->billingStatus = $billingStatus;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="multiplier", type="string", length=255, nullable=true)
     */
    private $multiplier;

    /**
     * @var string
     *
     * Technical support code to current reading
     *
     * @ORM\Column(name="ot_code", type="string", length=255, nullable=true)
     */
    private $OtCode;

    /**
     * @var string
     *
     * Technical support code to previous reading
     *
     * @ORM\Column(name="otp_code", type="string", length=255, nullable=true)
     */
    private $OtpCode;

    /**
     * @var string
     *
     * @ORM\Column(name="obis", type="string", length=255, nullable=true)
     */
    private $obis;

    /**
     * @var string
     *
     * @ORM\Column(name="loss_percentage", type="string", length=255, nullable=true)
     */
    private $lossPercentage;

    /**
     * @var string
     *
     * @ORM\Column(name="consumption_correction", type="string", length=255, nullable=true)
     */
    private $consumptionCorrection;

    /**
     * @var string
     *
     * @ORM\Column(name="consumption_including_loss", type="string", length=255, nullable=true)
     */
    private $consumptionIncludingLoss;

    /**
     * @var string
     *
     * @ORM\Column(name="consumption_loss_kwh", type="string", length=255, nullable=true)
     */
    private $consumptionLossKwh;

    /**
     * @return string
     */
    public function getConsumptionLossKwh()
    {
        return $this->consumptionLossKwh;
    }

    /**
     * @param string $consumptionLossKwh
     */
    public function setConsumptionLossKwh($consumptionLossKwh)
    {
        $this->consumptionLossKwh = $consumptionLossKwh;
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
    public function getPpCode()
    {
        return $this->ppCode;
    }

    /**
     * @param string $ppCode
     */
    public function setPpCode($ppCode)
    {
        $this->ppCode = $ppCode;
    }

    /**
     * @return string
     */
    public function getDeviceId()
    {
        return $this->deviceId;
    }

    /**
     * @param string $deviceId
     */
    public function setDeviceId($deviceId)
    {
        $this->deviceId = $deviceId;
    }

    /**
     * @return string
     */
    public function getDeviceSerialNumber()
    {
        return $this->deviceSerialNumber;
    }

    /**
     * @param string $deviceSerialNumber
     */
    public function setDeviceSerialNumber($deviceSerialNumber)
    {
        $this->deviceSerialNumber = $deviceSerialNumber;
    }

    /**
     * @return string
     */
    public function getReadingId()
    {
        return $this->readingId;
    }

    /**
     * @param string $readingId
     */
    public function setReadingId($readingId)
    {
        $this->readingId = $readingId;
    }

    /**
     * @return string
     */
    public function getArea()
    {
        return $this->area;
    }

    /**
     * @param string $area
     */
    public function setArea($area)
    {
        $this->area = $area;
    }

    /**
     * @return string
     */
    public function getAreaOriginal()
    {
        return $this->areaOriginal;
    }

    /**
     * @param string $areaOriginal
     */
    public function setAreaOriginal($areaOriginal)
    {
        $this->areaOriginal = $areaOriginal;
    }

    /**
     * @return string
     */
    public function getSeller()
    {
        return $this->seller;
    }

    /**
     * @param string $seller
     */
    public function setSeller($seller)
    {
        $this->seller = $seller;
    }

    /**
     * @return string
     */
    public function getTariff()
    {
        return $this->tariff;
    }

    /**
     * @param string $tariff
     */
    public function setTariff($tariff)
    {
        $this->tariff = $tariff;
    }

    /**
     * @return string
     */
    public function getClientName()
    {
        return $this->clientName;
    }

    /**
     * @param string $clientName
     */
    public function setClientName($clientName)
    {
        $this->clientName = $clientName;
    }

    /**
     * @return string
     */
    public function getClientAddress()
    {
        return $this->clientAddress;
    }

    /**
     * @param string $clientAddress
     */
    public function setClientAddress($clientAddress)
    {
        $this->clientAddress = $clientAddress;
    }

    /**
     * @return string
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * @param string $device
     */
    public function setDevice($device)
    {
        $this->device = $device;
    }

    /**
     * @return string
     */
    public function getStateStart()
    {
        return $this->stateStart;
    }

    /**
     * @param string $stateStart
     */
    public function setStateStart($stateStart)
    {
        $this->stateStart = $stateStart;
    }

    /**
     * @return mixed
     */
    public function getCalculatedStateStart()
    {
        return $this->calculatedStateStart;
    }

    /**
     * @param mixed $calculatedStateStart
     */
    public function setCalculatedStateStart($calculatedStateStart)
    {
        $this->calculatedStateStart = $calculatedStateStart;
    }

    /**
     * @return string
     */
    public function getStateEnd()
    {
        return $this->stateEnd;
    }

    /**
     * @param string $stateEnd
     */
    public function setStateEnd($stateEnd)
    {
        $this->stateEnd = $stateEnd;
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
    public function getDateOfReading()
    {
        return $this->dateOfReading;
    }

    /**
     * @param string $dateOfReading
     */
    public function setDateOfReading($dateOfReading)
    {
        $this->dateOfReading = $dateOfReading;
    }

    /**
     * @return string
     */
    public function getConsumptionM()
    {
        return $this->consumptionM;
    }

    /**
     * @param string $consumptionM
     */
    public function setConsumptionM($consumptionM)
    {
        $this->consumptionM = $consumptionM;
    }

    /**
     * @return string
     */
    public function getConsumptionKwh()
    {
        return $this->consumptionKwh;
    }

    /**
     * @param string $consumptionKwh
     */
    public function setConsumptionKwh($consumptionKwh)
    {
        $this->consumptionKwh = $consumptionKwh;
    }

    /**
     * @return mixed
     */
    public function getCalculatedConsumptionM()
    {
        return $this->calculatedConsumptionM;
    }

    /**
     * @param mixed $calculatedConsumptionM
     */
    public function setCalculatedConsumptionM($calculatedConsumptionM)
    {
        $this->calculatedConsumptionM = $calculatedConsumptionM;
    }

    /**
     * @return mixed
     */
    public function getCalculatedConsumptionKwh()
    {
        return $this->calculatedConsumptionKwh;
    }

    /**
     * @param mixed $calculatedConsumptionKwh
     */
    public function setCalculatedConsumptionKwh($calculatedConsumptionKwh)
    {
        $this->calculatedConsumptionKwh = $calculatedConsumptionKwh;
    }

    /**
     * @return string
     */
    public function getRatio()
    {
        return $this->ratio;
    }

    /**
     * @param string $ratio
     */
    public function setRatio($ratio)
    {
        $this->ratio = $ratio;
    }

    /**
     * @return string
     */
    public function getEnergyType()
    {
        return $this->energyType;
    }

    /**
     * @param string $energyType
     */
    public function setEnergyType($energyType)
    {
        $this->energyType = $energyType;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    /**
     * @return string
     */
    public function getFileType()
    {
        return $this->fileType;
    }

    /**
     * @param string $fileType
     */
    public function setFileType($fileType)
    {
        $this->fileType = $fileType;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getReadingType()
    {
        return $this->readingType;
    }

    /**
     * @param string $readingType
     */
    public function setReadingType($readingType)
    {
        $this->readingType = $readingType;
    }

    /**
     * @return string
     */
    public function getReadingTypeOriginal()
    {
        return $this->readingTypeOriginal;
    }

    /**
     * @param string $readingTypeOriginal
     */
    public function setReadingTypeOriginal($readingTypeOriginal)
    {
        $this->readingTypeOriginal = $readingTypeOriginal;
    }

    /**
     * @return string
     */
    public function getMultiplier()
    {
        return $this->multiplier;
    }

    /**
     * @param string $multiplier
     */
    public function setMultiplier($multiplier)
    {
        $this->multiplier = $multiplier;
    }

    /**
     * @return string
     */
    public function getOtCode()
    {
        return $this->OtCode;
    }

    /**
     * @param string $OtCode
     */
    public function setOtCode($OtCode)
    {
        $this->OtCode = $OtCode;
    }

    /**
     * @return string
     */
    public function getOtpCode()
    {
        return $this->OtpCode;
    }

    /**
     * @param string $OtpCode
     */
    public function setOtpCode($OtpCode)
    {
        $this->OtpCode = $OtpCode;
    }

    /**
     * @return string
     */
    public function getObis()
    {
        return $this->obis;
    }

    /**
     * @param string $obis
     */
    public function setObis($obis)
    {
        $this->obis = $obis;
    }

    /**
     * @return string
     */
    public function getLossPercentage()
    {
        return $this->lossPercentage;
    }

    /**
     * @param string $lossPercentage
     */
    public function setLossPercentage($lossPercentage)
    {
        $this->lossPercentage = $lossPercentage;
    }

    /**
     * @return string
     */
    public function getConsumptionCorrection()
    {
        return $this->consumptionCorrection;
    }

    /**
     * @param string $consumptionCorrection
     */
    public function setConsumptionCorrection($consumptionCorrection)
    {
        $this->consumptionCorrection = $consumptionCorrection;
    }

    /**
     * @return string
     */
    public function getConsumptionIncludingLoss()
    {
        return $this->consumptionIncludingLoss;
    }

    /**
     * @param string $consumptionIncludingLoss
     */
    public function setConsumptionIncludingLoss($consumptionIncludingLoss)
    {
        $this->consumptionIncludingLoss = $consumptionIncludingLoss;
    }

    public function __toString()
    {
        return (string) $this->id;
    }

    /**
     * @ORM\PrePersist
     */
    public function updatedTimestamps()
    {
        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new \DateTime());
        }
    }
}

