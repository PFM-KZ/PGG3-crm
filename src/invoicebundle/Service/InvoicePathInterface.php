<?php

namespace Wecoders\InvoiceBundle\Service;

interface InvoicePathInterface
{
    public function getNumber();
    public function getCreatedDate();
}