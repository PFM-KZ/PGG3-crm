<?php

namespace Wecoders\EnergyBundle\Service;

use Doctrine\ORM\EntityManager;
use Wecoders\EnergyBundle\Entity\DocumentTable\TableHeading;
use Wecoders\EnergyBundle\Entity\InvoiceInterface;

class DocumentTableModel
{
    const ENTITY = 'WecodersEnergyBundle:DocumentTable\DocumentTable';

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getRecordByToken($token)
    {
        return $this->em->getRepository(self::ENTITY)->findOneBy(['token' => $token]);
    }

    public function getRecords()
    {
        return $this->em->getRepository(self::ENTITY)->findAll();
    }

    public function generateHeadings($headings)
    {
        if (!$headings) {
            return [];
        }

        $result = [];
        /** @var TableHeading $heading */
        foreach ($headings as $heading) {
            $text = $heading->getText();
            $width = $heading->getWidth();

            $row = [];
            if ($text) {
                $row['text'] = $text;
            }
            if ($width) {
                $row['width'] = $width;
            }

            $result[] = $row;
        }

        return $result;
    }

    public function generateRows($invoice, $billingPeriodFrom, $billingPeriodTo, $isEnergy)
    {

    }
}