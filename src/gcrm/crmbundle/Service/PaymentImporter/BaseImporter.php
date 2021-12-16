<?php

namespace GCRM\CRMBundle\Service\PaymentImporter;

use Doctrine\ORM\EntityManagerInterface;
use GCRM\CRMBundle\Service\PaymentImporter\Exception\FileAlreadyExistException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Wecoders\EnergyBundle\Service\SpreadsheetReader;

abstract class BaseImporter implements PaymentImporterInterface
{
    const RELATIVE_DIR_PATH = 'var/data/payments';

    /** @var  UploadedFile */
    protected $file;

    protected $filename;

    /** @var  SpreadsheetReader */
    protected $spreadsheetReader;

    protected $kernelRootDir;

    /** @var  EntityManagerInterface */
    protected $em;

    protected $dirName;

    protected $relativeDirPath;

    protected $absoluteDirPath;

    protected $readerType;

    protected $readFromRow;

    protected $endColumnChar;

    protected $readerOptions;

    public function initDir()
    {
        $this->relativeDirPath = self::RELATIVE_DIR_PATH . '/' . $this->dirName;
        $this->absoluteDirPath = $this->kernelRootDir . '/../' . $this->relativeDirPath;
    }

    public function init(UploadedFile $file)
    {
        $this->file = $file;
        $this->manageFilename();
        $this->initDir();
    }

    private function manageFilename()
    {
        $originalName = $this->file->getClientOriginalName();
        $pos = strrpos($originalName, '.');
        if ($pos !== false) {
            $filenameWithoutExtension = substr($originalName, 0, $pos);
        } else {
            $filenameWithoutExtension = $originalName;
        }

        $this->filename = $filenameWithoutExtension;
    }

    public function getFiles()
    {
        $dir = [];
        if (!file_exists($this->absoluteDirPath)) {
            return $dir;
        }
        $this->generateFilesStructure($this->absoluteDirPath, $dir);
        return $dir;
    }

    /**
     * @param string $dir
     * @return void
     */
    protected function generateFilesStructure($dir, &$dir_array)
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

    protected function fetchHeaderRow(&$rows)
    {
        foreach ($rows as $row) {
            return $row;
        }
        return null;
    }

    protected function fetchDataRows(&$rows)
    {
        $tmpRows = [];
        $index = 0;

        foreach ($rows as $row) {
            if ($index > 0) {
                $tmpRows[] = $row;
            }

            $index++;
        }

        return $tmpRows;
    }

    protected function validateFileExist()
    {
        $errorMessage = 'Błąd, zmiany nie zostały wprowadzone. Taki plik znajduje się już w systemie.';

        // check if file is already on server (if so - it can't be uploaded)
        $dir = [];
        $this->generateFilesStructure($this->absoluteDirPath, $dir);
        foreach ($dir as $keys) {
            foreach ($keys as $filename) {
                if ($this->filename == $filename) {
                    throw new FileAlreadyExistException($errorMessage);
                }
            }
        }
    }

    protected function createTemporaryFile($iconvFrom = null, $iconvTo = null)
    {
        $tmpFilename = 'tmp.txt';
        $fileContent = file_get_contents($this->file);
        if ($iconvFrom && $iconvTo) {
            $fileContent = iconv($iconvFrom, $iconvTo, $fileContent);
        }
        $tmpFileAbsolutePath = $this->absoluteDirPath . '/' . $tmpFilename;
        file_put_contents($tmpFileAbsolutePath, $fileContent);

        return $tmpFileAbsolutePath;
    }

    protected function manageDir()
    {
        if (!file_exists($this->absoluteDirPath)) {
            mkdir($this->absoluteDirPath);
        }
    }

    protected function uploadFile()
    {
        $this->file->move($this->absoluteDirPath, $this->filename);
    }
}