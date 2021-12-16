<?php

namespace GCRM\CRMBundle\Service;

interface ListDataExporterInterface
{
    public function getDataToExport(&$records, $statusDepartment, $listDataExporterTable = null);
}