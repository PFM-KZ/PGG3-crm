<?php

namespace GCRM\CRMBundle\Controller;

use \Symfony\Bundle\TwigBundle\Controller\ExceptionController as BaseClass;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class ExceptionController extends BaseClass
{
    private $kernelEnvironment;

    public function __construct($debug, $kernelEnvironment, Environment $twig)
    {
        $this->kernelEnvironment = $kernelEnvironment;

        parent::__construct($twig, $debug);
    }

    /**
     * Converts an Exception to a Response.
     *
     * A "showException" request parameter can be used to force display of an error page (when set to false) or
     * the exception page (when true). If it is not present, the "debug" value passed into the constructor will
     * be used.
     *
     * @return Response
     *
     * @throws \InvalidArgumentException When the exception template does not exist
     */
    public function showExceptionAction(Request $request, FlattenException $exception, DebugLoggerInterface $logger = null)
    {
        $currentContent = $this->getAndCleanOutputBuffering($request->headers->get('X-Php-Ob-Level', -1));
        $showException = $request->attributes->get('showException', $this->debug); // As opposed to an additional parameter, this maintains BC

        $code = $exception->getStatusCode();




        $isAdminPage = substr($request->getRequestUri(), 0, 6) == '/admin' ? true : false;
        if ($isAdminPage && $this->kernelEnvironment == 'prod') {
//        if ($isAdminPage) {
            $customMessage = null;
            if ($exception->getClass() == 'Doctrine\DBAL\Exception\UniqueConstraintViolationException') {
                $customMessage = 'Wystąpił błąd. Nie można dodać rekordu, ponieważ podobny rekord już wcześniej został dodany. Sprawdź poprawność unikalnych pól i spróbuj ponownie.';
            } elseif ($exception->getClass() == 'GCRM\CRMBundle\Service\RecordReservedByOtherUserException') {
                $customMessage = 'Wystąpił błąd. Inny użytkownik jest w trakcie edycji tego rekordu. Wybierz inny rekord.';
            } elseif ($exception->getClass() == 'GCRM\CRMBundle\Service\CompanyWithBranchAccessRestrictedException') {
                $customMessage = 'Brak dostępu. Twoje ustawienia oddziału (firma / marka) nie pozwalają na edycję tego rekordu. Sprawdź swoje ustawienia firm i marek do których należysz lub dany rekord czy został przypisany do odpowiedniego oddziału (firma / marka).';
            } elseif ($exception->getClass() == 'GCRM\CRMBundle\Service\AccessRestrictedException') {
                $customMessage = 'Brak dostępu. Nie posiadasz odpowiednich uprawnień do wyświetlenia danej strony.';
            }


            // display custom error template only if this funcionality is programmed (exception is catched and maked)
            if ($customMessage) {
                return new Response($this->twig->render('GCRMCRMBundle:Default:exception.html.twig', [
                        'status_code' => $code,
                        'status_text' => isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '',
                        'exception' => $exception,
                        'logger' => $logger,
                        'currentContent' => $currentContent,
                        'customMessage' => $customMessage,
                    ]
                ), 200, array('Content-Type' => $request->getMimeType($request->getRequestFormat()) ?: 'text/html'));
            }
        }




        return new Response($this->twig->render(
            (string) $this->findTemplate($request, $request->getRequestFormat(), $code, $showException),
            array(
                'status_code' => $code,
                'status_text' => isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '',
                'exception' => $exception,
                'logger' => $logger,
                'currentContent' => $currentContent,
            )
        ), 200, array('Content-Type' => $request->getMimeType($request->getRequestFormat()) ?: 'text/html'));
    }
}
