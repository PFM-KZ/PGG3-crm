<?php

namespace GCRM\CRMBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ServiceProvider
 *
 * @ORM\Table(name="service_provider")
 * @ORM\Entity(repositoryClass="GCRM\CRMBundle\Repository\ServiceProviderRepository")
 */
class ServiceProvider
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
     * @ORM\Column(name="uke_nr", type="string", length=255)
     */
    private $ukeNr;


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
     * @return ServiceProvider
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
     * Set ukeNr
     *
     * @param string $ukeNr
     *
     * @return ServiceProvider
     */
    public function setUkeNr($ukeNr)
    {
        $this->ukeNr = $ukeNr;

        return $this;
    }

    /**
     * Get ukeNr
     *
     * @return string
     */
    public function getUkeNr()
    {
        return $this->ukeNr;
    }

    public function __toString()
    {
        return $this->title . ' - UKE: ' . $this->getUkeNr();
    }
}

