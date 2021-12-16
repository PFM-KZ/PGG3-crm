<?php

namespace GCRM\CRMBundle\Service;

use stringEncode\Exception;
use Symfony\Component\HttpKernel\Kernel;

class DataUploader
{
    const ROOT_RELATIVE_DATA_PATH = 'var/data/';
    const ROOT_RELATIVE_UPLOADS_PATH = 'var/data/uploads/';

    private $maxFileSizeMb = 500;

    public function createPrivateUploadDirectory($kernelRootDir)
    {
        $dataDir = $kernelRootDir . '/../' . self::ROOT_RELATIVE_DATA_PATH;
        if (!file_exists($dataDir)) {
            mkdir($dataDir);
        }

        $uploadDir = $kernelRootDir . '/../' . self::ROOT_RELATIVE_UPLOADS_PATH;
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir);
        }
    }

    /**
     * @param array $filesExtensionsAllowed
     * @return bool
     * @throws \Exception
     */
    public function uploadAttachment($filesExtensionsAllowed, $pathToSave)
    {
        $uploadedFiles = false;

        if (!isset($_FILES['formFile']['name'][0])) {
            throw new \Exception('Brak plików do przesłania');
        }

        $errorsList = array();
        for ($i = 0; $i < count($_FILES['formFile']['name']);++$i) {
            $error = $this->validateAttachment($i, $filesExtensionsAllowed);

            if (!$error) {
                $extension = strtolower(substr($_FILES['formFile']['name'][$i], strrpos($_FILES['formFile']['name'][$i], ".") + 1));
                $fileName = @ substr($_FILES['formFile']['name'][$i], 0, strrpos($_FILES['formFile']['name'][$i], "."));
                $fileName = $this->replaceSpecialChars($fileName);

                $slashPathModel = new SlashPathModel();
                $pathToSave = $slashPathModel->addSlash($pathToSave);

                $path = $pathToSave . $fileName . '.' . strtolower($extension);

                if (file_exists($path)) {
                    $index = 1;
                    do {
                        $path = $pathToSave . $fileName . '('.$index.').' . strtolower($extension);
                        $index++;
                    } while (file_exists($path));
                }

                $uploadedFiles = move_uploaded_file($_FILES['formFile']['tmp_name'][$i], $path);
            } else {
                $errorsList[] = $error;
            }
        }

        if (count($errorsList)) {
            throw new \Exception(implode('<br>', $errorsList));
        }

        return $uploadedFiles;
    }

    /**
     * @param int $i
     * @param array $filesExtensionsAllowed
     * @return string|false
     */
    private function validateAttachment($i, $filesExtensionsAllowed)
    {
        if (!is_uploaded_file($_FILES['formFile']['tmp_name'][$i])) {
            return 'is_uploaded_file error';
        }

        $extension = strtolower(substr($_FILES['formFile']['name'][$i], strrpos($_FILES['formFile']['name'][$i], ".") + 1));
        if (!in_array($extension, $filesExtensionsAllowed)) {
            return 'Niepoprawny format pliku' . $_FILES['formFile']['name'][$i] . '.';
        }

        if ($_FILES['formFile']['error'][$i] != 0) {
            return 'Wystąpił błąd przy przesyłaniu pliku' . $_FILES['formFile']['name'][$i];
        }

        if ($_FILES["formFile"]["size"][$i] >= $this->maxFileSizeMb * 1024 * 1024) {
            return 'Wysłany plik ' . $_FILES['formFile']['name'][$i] . ' jest za duży';
        }

        return false;
    }

    /**
     * @param string $text
     * @return string
     */
    private function replaceSpecialChars($text)
    {
        $old = ["ó", "ą", "ę", "ł", "ń", "ż", "ź", "ć", "ś", "Ą", "Ó", "Ę", "Ł", "Ń", "Ż", "Ź", "Ć", "Ś", " ", "ä", "Ä", "ö", "Ö", "ü", "Ü", "ß"];
        $new = ["o", "a", "e", "l", "n", "z", "z", "c", "s", "A", "O", "E", "L", "N", "Z", "Z", "C", "S", "_", "a", "A", "o", "O", "u", "U", "B"];
        $text = str_replace($old, $new, $text);
        $text = preg_replace( '/[^a-zA-Z0-9_]+/i', '', $text );
        $text = str_replace('__', '_', $text);

        return $text;
    }
}