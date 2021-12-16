<?php

namespace AppBundle\Event;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class MaintenanceListener
{
    private $maintenanceFilePath;

    private $allowedCookieNamePass;

    private $templating;

    public function __construct($maintenanceFilePath, $allowedCookieNamePass, ContainerInterface $container)
    {
        $this->maintenanceFilePath = $maintenanceFilePath;
        $this->allowedCookieNamePass = $allowedCookieNamePass;
        $this->templating = $container->get('templating');
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!file_exists($this->maintenanceFilePath)) {
            return;
        }

        if ($event->getRequest()->cookies->has($this->allowedCookieNamePass)) {
            return;
        }

        $event->setResponse(
            new Response($this->templating->render('maintenance.html.twig'),
            Response::HTTP_SERVICE_UNAVAILABLE)
        );
        $event->stopPropagation();
    }
}