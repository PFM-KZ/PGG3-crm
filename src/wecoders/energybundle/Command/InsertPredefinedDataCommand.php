<?php

namespace Wecoders\EnergyBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Service\EasyAdminModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wecoders\InvoiceBundle\Entity\InvoiceTemplate;

class InsertPredefinedDataCommand extends Command
{
    private $em;
    private $easyAdminModel;

    public function __construct(EntityManager $em, EasyAdminModel $easyAdminModel)
    {
        $this->em = $em;
        $this->easyAdminModel = $easyAdminModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('wecodersinvoicebundle:insert-predefined-data')
            ->setDescription('Inserts bundle predefined data.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $help = '
';

        $code = $this->easyAdminModel->getInvoiceTemplateCodeByEntityName('InvoiceProformaEnergy');
        if (!$this->checkIfTemplateAlreadyExist($code)) {
            $invoiceTemplate = new InvoiceTemplate();
            $invoiceTemplate->setTitle('Faktura proforma');
            $invoiceTemplate->setCode($code);
            $invoiceTemplate->setHelp($help);

            $this->em->persist($invoiceTemplate);
            $this->em->flush();
            dump('Added: ' . $code);
        }

        $code = $this->easyAdminModel->getInvoiceTemplateCodeByEntityName('InvoiceProformaCorrectionEnergy');
        if (!$this->checkIfTemplateAlreadyExist($code)) {
            $invoiceTemplate = new InvoiceTemplate();
            $invoiceTemplate->setTitle('Korekta faktury proforma');
            $invoiceTemplate->setCode($code);
            $invoiceTemplate->setHelp($help);

            $this->em->persist($invoiceTemplate);
            $this->em->flush();
            dump('Added: ' . $code);
        }
    }

    private function checkIfTemplateAlreadyExist($code)
    {
        return $this->em->getRepository('WecodersInvoiceBundle:InvoiceTemplate')->findOneBy(['code' => $code]);
    }
}