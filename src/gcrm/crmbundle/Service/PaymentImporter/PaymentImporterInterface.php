<?php

namespace GCRM\CRMBundle\Service\PaymentImporter;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface PaymentImporterInterface
{
    public function init(UploadedFile $file);
    public function initDir();
    public function execute();
    public function getFiles();
}