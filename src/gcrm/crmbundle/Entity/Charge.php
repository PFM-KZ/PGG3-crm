<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Charge
 *
 * @ORM\Table(name="charge")
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\ChargeRepository")
 */
class Charge
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
     * @ORM\Column(name="include_numbers", type="text")
     */
    private $includeNumbers;

    /**
     * @var string
     *
     * @ORM\Column(name="exclude_numbers", type="text")
     */
    private $excludeNumbers;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive;


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
     * Set includeNumbers
     *
     * @param string $includeNumbers
     *
     * @return Charge
     */
    public function setIncludeNumbers($includeNumbers)
    {
        $this->includeNumbers = $includeNumbers;

        return $this;
    }

    /**
     * Get includeNumbers
     *
     * @return string
     */
    public function getIncludeNumbers()
    {
        return $this->includeNumbers;
    }

    /**
     * Set excludeNumbers
     *
     * @param string $excludeNumbers
     *
     * @return Charge
     */
    public function setExcludeNumbers($excludeNumbers)
    {
        $this->excludeNumbers = $excludeNumbers;

        return $this;
    }

    /**
     * Get excludeNumbers
     *
     * @return string
     */
    public function getExcludeNumbers()
    {
        return $this->excludeNumbers;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return Charge
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return bool
     */
    public function getIsActive()
    {
        return $this->isActive;
    }
}

