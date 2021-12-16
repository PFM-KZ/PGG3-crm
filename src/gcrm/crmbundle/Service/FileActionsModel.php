<?php

namespace GCRM\CRMBundle\Service;

class FileActionsModel
{
    /**
     * @param string $dir
     * @return void
     */
    public function generateFilesStructure($dir, &$dir_array)
    {
        $files = scandir($dir);
        if (!isset($dir_array[$dir])) {
            $dir_array[$dir] = array();
        }
        if (is_array($files)) {
            foreach ($files as $val) {
                if ($val == '.' || $val == '..') {
                    continue;
                }
                $dir_array[$dir][] = $val;
                if (is_dir($dir . '/' . $val)) {
                    $this->generateFilesStructure($dir . '/' . $val, $dir_array);
                }
            }
        }
        ksort($dir_array);
    }

    public function deleteFiles($dir, $files)
    {
        if ($files === null) {
            return;
        }

        foreach ($files as $file) {
            unlink($dir . '/' . $file);
        }
    }
}