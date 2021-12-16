<?php

namespace Wecoders\EnergyBundle\Entity;

interface HasIncludedDocumentsInterface
{
    public function getIncludedDocuments();
    public function setIncludedDocuments($data);
}
