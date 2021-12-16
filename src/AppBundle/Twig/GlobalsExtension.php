<?php

namespace AppBundle\Twig;

use Doctrine\ORM\EntityManager;
use Twig_Extension;
use Twig_Extension_GlobalsInterface;

class GlobalsExtension extends Twig_Extension implements Twig_Extension_GlobalsInterface
{
    /** @var EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getGlobals()
    {
        $settings = $this->em->getRepository('GCRMCRMBundle:Settings\System')
            ->findAll();

        return [
            'settings' => ['system' => $settings]
        ];
    }
}