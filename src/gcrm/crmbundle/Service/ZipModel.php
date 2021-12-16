<?php

namespace GCRM\CRMBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class ZipModel
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function download($filesToDownload, $preZipName = '', $generateRandomNumber = true, $zipName = '', $unlink = false)
    {
        $directoryOutput = $this->container->get('kernel')->getRootDir() . '/../var/';

        $zip = new \ZipArchive();
        if ($generateRandomNumber) {
            $zipName = $preZipName . '-' . time() . rand(1,999) . rand(1,999) . '.zip';
        } else {
            $zipName .= '.zip';
        }

        $zip->open($directoryOutput . $zipName,  \ZipArchive::CREATE);
        foreach ($filesToDownload as $f) {
            $zip->addFromString(basename($f),  file_get_contents($f));
        }
        $zip->close();

        header_remove();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipName . '"');
        header('Content-length: ' . filesize($directoryOutput . $zipName));
        echo readfile($directoryOutput . $zipName);
        if ($unlink) {
            unlink($directoryOutput . $zipName);
        }
    }

    public function downloadZip($fullPath, $filename)
    {
        header_remove();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-length:' . filesize($fullPath));
        echo readfile($fullPath);
        die;
    }

    public function generate($filesPaths, $outputFilePath)
    {
        $zip = new \ZipArchive();

        $zip->open($outputFilePath,  \ZipArchive::CREATE);
        foreach ($filesPaths as $f) {
            $zip->addFromString(basename($f),  file_get_contents($f));
        }
        $zip->close();
    }
}