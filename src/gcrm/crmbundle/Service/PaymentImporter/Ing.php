<?php

namespace GCRM\CRMBundle\Service\PaymentImporter;

use Doctrine\ORM\EntityManagerInterface;
use GCRM\CRMBundle\Entity\Payment;
use GCRM\CRMBundle\Service\CompanyModel;
use GCRM\CRMBundle\Service\PaymentImporter\Exception\InvalidBankAccountException;
use GCRM\CRMBundle\Service\PaymentImporter\Exception\InvalidFilenameException;
use GCRM\CRMBundle\Service\PaymentImporterModel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Service\SpreadsheetReader;

class Ing extends BaseImporter
{
    const DIR_NAME = 'ing';

    private $container;

    protected $readerType = 'Csv';

    protected $readFromRow = 1;

    protected $endColumnChar = 'I';

    protected $readerOptions = [
        'setDelimiter' => ',',
        'setEnclosure' => ''
    ];

    public function __construct(EntityManagerInterface $em, SpreadsheetReader $spreadsheetReader, ContainerInterface $container, $kernelRootDir)
    {
        $this->spreadsheetReader = $spreadsheetReader;
        $this->kernelRootDir = $kernelRootDir;
        $this->em = $em;
        $this->dirName = self::DIR_NAME;
        $this->container = $container;
    }

    protected function createTemporaryFile($iconvFrom = null, $iconvTo = null)
    {
        $tmpFilename = 'tmp.txt';
        $fileContent = file_get_contents($this->file);
        if ($iconvFrom && $iconvTo) {
            $fileContent = iconv($iconvFrom, $iconvTo, $fileContent);
        }
        $fileContent = preg_replace('/\\\\"/', '"', $fileContent);

        $tmpFileAbsolutePath = $this->absoluteDirPath . '/' . $tmpFilename;
        file_put_contents($tmpFileAbsolutePath, $fileContent);

        return $tmpFileAbsolutePath;
    }


    public function execute()
    {
        $this->manageDir();
        $this->validateFilename();
        $this->validateFileExist();

        $tmpFileAbsolutePath = $this->createTemporaryFile('ISO 8859-2', 'UTF-8');

        // fetch rows
        $rows = $this->spreadsheetReader->fetchRows(
            $this->readerType,
            $tmpFileAbsolutePath,
            $this->readFromRow,
            $this->endColumnChar,
            $this->readerOptions
        );

        // delete tmp file, its readed already
        unlink($tmpFileAbsolutePath);

        // no data (empty file), so return and do nothing
        if (!$rows) {
            return;
        }

        $this->validateBankAccountNumber($rows[1][2]);

        $headerRow = $this->fetchHeaderRow($rows);
        $dataRows = $this->fetchDataRows($rows);
        $payments = $this->persistData($headerRow, $dataRows);
        $this->uploadFile();
        $this->em->flush();

        return $payments;
    }

    private function persistData($headings, $rows)
    {
        $payments = [];
        foreach ($rows as $row) {
            if ($row[1] != 'Incoming Payment') {
                die('Oczekiwana usługa: Incoming Payment, usługa z odczytu to: ' . $row[1]);
            }

            $payment = new Payment();

            $payment->setBadgeId($row[3]);
            $value = $row[4] / 100;
            $payment->setValue($value);

            $payment->setSenderBranchNumber(null);
            $payment->setReceiverBranchNumber(null);
            $payment->setSenderAccountNumber($headings[2]); // todo: check if its not number from headings
            $payment->setReceiverAccountNumber(null);
            $payment->setSenderName($row[8]);
            $payment->setReceiverName(null);

            $year = substr($row[5], 0, 4); // todo: check if date is correct or should be taken from headings
            $month = substr($row[5], 4, 2);
            $day = substr($row[5], 6, 2);

            $date = new \DateTime();
            $date->setDate($year, $month, $day);
            $date->setTime(0, 0, 0);

            $payment->setDate($date);
            $payment->setData(implode($headings, ',') . '###' . implode($row, ','));

            $payment->setFilename($this->filename);
            $payment->setCode(PaymentImporterModel::BANK_TYPE_ING);

            $payments[] = $payment;

            $this->em->persist($payment);
        }

        return $payments;
    }

    /**
     * @throws InvalidFilenameException
     */
    private function validateFilename()
    {
        $errorMessage = 'Błędna nazwa pliku. Prawidłowa struktura to: RRRRMMDD-NR_GENEROWANEGO_PLIKU_Z_DANEGO_DNIA';

        $parts = explode('-', $this->filename);

        if (count($parts) != 2) {
            throw new InvalidFilenameException($errorMessage);
        }

        if (!is_numeric($parts[0]) || !is_numeric($parts[1])) {
            throw new InvalidFilenameException($errorMessage);
        }

        if (mb_strlen($parts[0]) != 8) {
            throw new InvalidFilenameException($errorMessage);
        }

        if (strpos($parts[1], '.') !== false) {
            throw new InvalidFilenameException($errorMessage);
        }
    }

    private function validateBankAccountNumber($bankAccountNumber)
    {
        $errorMessage = 'Błędny numer rachunku bankowego. Sprawdź czy wgrywasz prawidłowy plik.';

        /** @var CompanyModel $companyModel */
        $companyModel = $this->container->get('GCRM\CRMBundle\Service\CompanyModel');
        if (!$companyModel->getCompanyByBankAccountNumber($bankAccountNumber)) {
            throw new InvalidBankAccountException($errorMessage);
        }
    }

}