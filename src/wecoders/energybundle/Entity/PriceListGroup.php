<?php

namespace Wecoders\EnergyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PriceListGroup
 *
 * @ORM\Table(name="price_list_group")
 * @ORM\Entity(repositoryClass="Wecoders\EnergyBundle\Repository\PriceListGroupRepository")
 * @ORM\HasLifecycleCallbacks
 */
class PriceListGroup
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
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="show_in_authorization", type="boolean")
     */
    protected $showInAuthorization;

    /**
     * @return string
     */
    public function getShowInAuthorization()
    {
        return $this->showInAuthorization;
    }

    /**
     * @param string $showInAuthorization
     */
    public function setShowInAuthorization($showInAuthorization)
    {
        $this->showInAuthorization = $showInAuthorization;
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
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function __toString()
    {
        return $this->title;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function updatedTimestamps()
    {
        if ($this->showInAuthorization == null) {
            $this->showInAuthorization = false;
        }
    }
}

