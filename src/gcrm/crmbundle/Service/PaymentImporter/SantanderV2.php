<?php

namespace GCRM\CRMBundle\Service\PaymentImporter;

use Doctrine\ORM\EntityManagerInterface;
use GCRM\CRMBundle\Entity\Payment;
use GCRM\CRMBundle\Service\PaymentImporter\Exception\InvalidFilenameException;
use GCRM\CRMBundle\Service\PaymentImporterModel;
use Wecoders\EnergyBundle\Service\SpreadsheetReader;

class SantanderV2 extends BaseImporter
{
    const DIR_NAME = 'santander-v2';

    protected $readerType = 'Csv';

    protected $readFromRow = 2;

    protected $endColumnChar = 'R';

    protected $readerOptions = [
        'setDelimiter' => '|',
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

        $tmpFileAbsolutePath = $this->createTemporaryFile('CP1250', 'UTF-8');

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

        $payments = $this->persistData($rows);
        $this->uploadFile();
        $this->em->flush();

        return $payments;
    }

    private function persistData($rows)
    {
        $payments = [];
        // reset indexes
        if ($rows && count($rows)) {
            $rows = array_values($rows);
        }

        foreach ($rows as $key => $row) {
            $isLastIteration = $key == count($rows) - 1;
            if ($isLastIteration) {
                continue;
            }

            $payment = new Payment();

            $badgeId = substr($row[4], 14);
            $payment->setBadgeId($badgeId);
            $value = $row[2] * 1;
            $payment->setValue($value);
            $payment->setReceiverAccountNumber($row[4]);

            $payment->setSenderAccountNumber('');

            $payment->setSenderName($row[3]);

            $day = substr($row[1], 0, 2);
            $month = substr($row[1], 2, 2);
            $year = substr($row[1], 4, 4);

            $date = new \DateTime();
            $date->setDate($year, $month, $day);
            $date->setTime(0, 0, 0);

            $payment->setDate($date);
            $payment->setData('"' . implode($row, '","') . '"');

            $payment->setFilename($this->filename);
            $payment->setCode(PaymentImporterModel::BANK_TYPE_SANTANDER_V2);

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

        if (!is_numeric($this->filename) || mb_strlen($this->filename) != 16) {
            throw new InvalidFilenameException($errorMessage);
        }
    }

}