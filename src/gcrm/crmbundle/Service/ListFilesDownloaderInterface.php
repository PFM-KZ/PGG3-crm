<?php

namespace GCRM\CRMBundle\Service;

interface ListFilesDownloaderInterface
{
    public function getRelativeRootPathToDirectory($entityClass = null);

    public function getFilesToDownload($kernelRootDir, $records, $entityClass = null);
}