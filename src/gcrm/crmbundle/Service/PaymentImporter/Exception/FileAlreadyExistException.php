<?php

namespace GCRM\CRMBundle\Service\PaymentImporter\Exception;

use Throwable;

class FileAlreadyExistException extends \Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}