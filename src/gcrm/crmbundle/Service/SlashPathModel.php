<?php

namespace GCRM\CRMBundle\Service;

class SlashPathModel
{
    public function addSlash($path)
    {
        if ($path[strlen($path) - 1] != '/') {
            $path = $path . '/';
        }

        return $path;
    }
}