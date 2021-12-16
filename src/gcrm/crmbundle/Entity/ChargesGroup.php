<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ChargesGroup
 *
 * @ORM\Table(name="charges_group")
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\ChargesGroupRepository")
 */
class ChargesGroup
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
     * @var string
     *
     * @ORM\Column(name="serviceTypes", type="string", length=255, nullable=true)
     */
    private $serviceTypes;

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
     * Set title
     *
     * @param string $title
     *
     * @return ChargesGroup
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

    /**
     * @return string
     */
    public function getServiceTypes()
    {
        return $this->serviceTypes;
    }

    /**
     * @param string $serviceTypes
     */
    public function setServiceTypes($serviceTypes)
    {
        $this->serviceTypes = $serviceTypes;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return ChargesGroup
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

    public function __toString()
    {
        return $this->title;
    }
}

