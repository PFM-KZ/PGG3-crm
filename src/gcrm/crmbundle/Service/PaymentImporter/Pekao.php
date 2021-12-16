<?php

namespace GCRM\CRMBundle\Service\PaymentImporter;

use Doctrine\ORM\EntityManagerInterface;
use GCRM\CRMBundle\Entity\Payment;
use GCRM\CRMBundle\Service\PaymentImporter\Exception\InvalidFilenameException;
use GCRM\CRMBundle\Service\PaymentImporterModel;
use Wecoders\EnergyBundle\Service\SpreadsheetReader;

class Pekao extends BaseImporter
{
    const DIR_NAME = 'pekao';

    protected $readerType = 'Csv';

    protected $readFromRow = 1;

    protected $endColumnChar = 'S';

    protected $readerOptions = [
        'setDelimiter' => ',',
        'setEnclosure' => ''
    ];

    public function __construct(EntityManagerInterface $em, SpreadsheetReader $spreadsheetReader, $kernelRootDir)
    {
        $this->spreadsheetReader = $spreadsheetReader;
        $this->kernelRootDir = $kernelRootDir;
        $this->em = $em;
        $this->dirName = self::DIR_NAME;
    }

    public function execute()
    {
        $this->manageDir();
        $this->validateFilename();
        $this->validateFileExist();

        $tmpFileAbsolutePath = $this->createTemporaryFile('Windows-1250', 'UTF-8');

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

        $dataRows = $this->fetchDataRows($rows);

        $payments = $this->persistData($dataRows);
        $this->uploadFile();
        $this->em->flush();

        return $payments;
    }

    private function persistData($rows)
    {
        $payments = [];
        foreach ($rows as $row) {
            $payment = new Payment();

            $payment->setBadgeId($row[12]);
            $value = $row[3] / 100;
            $payment->setValue($value);

            $payment->setSenderBranchNumber(null);
            $payment->setReceiverBranchNumber(null);
            $payment->setSenderAccountNumber($row[2]);
            $payment->setReceiverAccountNumber(null);
            $payment->setSenderName($row[6] . ' ' . $row[7]);
            $payment->setReceiverName(null);

            $year = substr($row[5], 0, 4);
            $month = substr($row[5], 4, 2);
            $day = substr($row[5], 6, 2);

            $date = new \DateTime();
            $date->setDate($year, $month, $day);
            $date->setTime(0, 0, 0);

            $payment->setDate($date);
            $payment->setData(implode($row, ','));

            $payment->setFilename($this->filename);
            $payment->setCode(PaymentImporterModel::BANK_TYPE_PEKAO);

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
        $errorMessage = 'Błędna nazwa pliku.';

        if (!$this->filename || mb_strlen($this->filename) != 8) {
            throw new InvalidFilenameException($errorMessage);
        }
    }

    protected function fetchDataRows(&$rows)
    {
        $tmpRows = [];
        $index = 0;

        foreach ($rows as $row) {
            if ($index > 0 && $index < count($rows) - 1) {
                $tmpRows[] = $row;
            }

            $index++;
        }

        return $tmpRows;
    }

}