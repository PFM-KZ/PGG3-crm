<?php

namespace Wecoders\InvoiceBundle\Service;

class InvoiceTCPDF extends \TCPDF
{
    /** @var  FooterData */
    private $footerData;

    public function setFooterHtml(FooterData $footerData)
    {
        $this->footerData = $footerData;
    }

    public function Header()
    {

    }

    public function Footer()
    {
        $this->SetFont('dejavusans', '', 7);
        $this->SetY($this->footerData->getY());

        $this->MultiCell(0, 0, $this->footerData->getHtml(), 0, 'J', 0, 0, '', '', true, 0, true, true, 10, 'M');
    }
}