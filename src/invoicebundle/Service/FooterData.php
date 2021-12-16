<?php

namespace Wecoders\InvoiceBundle\Service;

class FooterData
{
    private $html;

    private $y;

    public function getHtml()
    {
        return $this->html;
    }

    public function setHtml($html)
    {
        $this->html = $html;

        return $this;
    }

    public function getY()
    {
        return $this->y;
    }

    public function setY($y)
    {
        $this->y = $y;

        return $this;
    }
}