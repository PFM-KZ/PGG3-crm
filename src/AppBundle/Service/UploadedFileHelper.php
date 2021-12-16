<?php

namespace AppBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;

class UploadedFileHelper
{
    const RELATIVE_DIR_PATH = 'var/data';

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getAbsoluteDirPath()
    {
        return $this->container->get('kernel')->getRootDir() . '/../' . self::RELATIVE_DIR_PATH;
    }

    public function getAbsoluteFilePath($filename)
    {
        return $this->getAbsoluteDirPath() . '/' . $filename;
    }

    private function removeTmpFile($filename)
    {
        $filePath = $this->getAbsoluteFilePath($filename);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    public function createTmpFile(\Symfony\Component\HttpFoundation\File\UploadedFile $file, $filename = 'tmp')
    {
        $this->removeTmpFile($filename);
        $file->move($this->getAbsoluteDirPath(), $filename);
        return $this->getAbsoluteFilePath($filename);
    }

}