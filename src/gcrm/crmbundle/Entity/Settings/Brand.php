<?php

namespace GCRM\CRMBundle\Entity\Settings;

use Doctrine\ORM\Mapping as ORM;

/**
 * System
 *
 * @ORM\Table(name="settings_brand")
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\SettingsRepository")
 */
class Brand
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
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="string", length=255, nullable=true)
     */
    private $value;

    /**
     * @ORM\Column(name="tooltip", type="text")
     */
    private $tooltip;

    /**
     * @ORM\Column(name="autoload", type="boolean")
     */
    private $autoload;

    public function __construct($name, $tooltip)
    {
        $this->name = $name;
        $this->tooltip = $tooltip;
        $this->autoload = false;
    }

    public function getTooltip()
    {
        return $this->tooltip;
    }

    public function getAutoload()
    {
        return $this->autoload;
    }

    public function setAutoload($autoload)
    {
        $this->autoload = $autoload;

        return $this;
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
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Brand
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

}

