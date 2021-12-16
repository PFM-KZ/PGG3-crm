<?php

namespace Wecoders\InvoiceBundle\Service;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InvoiceTemplateModel
{
    private $em;

    private $container;

    private $entity = 'WecodersInvoiceBundle:InvoiceTemplate';

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function getTemplateAbsoluteDirPath()
    {
        return $this->container->getParameter('vich.path.absolute.private.invoice_templates');
    }

    public function getTemplateAbsolutePath($filename)
    {
        return $this->getTemplateAbsoluteDirPath() . '/' . $filename;
    }

    public function getTemplateRecordByCode($code)
    {
        return $this->em->getRepository($this->entity)->findOneBy(['code' => $code]);
    }

}