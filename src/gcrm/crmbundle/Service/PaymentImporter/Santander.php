<?php

namespace GCRM\CRMBundle\Service\PaymentImporter;

use Doctrine\ORM\EntityManagerInterface;
use GCRM\CRMBundle\Entity\Payment;
use GCRM\CRMBundle\Service\PaymentImporter\Exception\InvalidFilenameException;
use GCRM\CRMBundle\Service\PaymentImporterModel;
use Wecoders\EnergyBundle\Service\SpreadsheetReader;

class Santander extends BaseImporter
{
    const DIR_NAME = 'santander';

    protected $readerType = 'Csv';

    protected $readFromRow = 1;

    protected $endColumnChar = 'R';

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
        foreach ($rows as $row) {
            $payment = new Payment();

            $badgeId = substr($row[6], 14);
            $payment->setBadgeId($badgeId);
            $value = $row[2] / 100;
            $payment->setValue($value);
            $payment->setSenderBranchNumber($row[3]);
            $payment->setReceiverBranchNumber($row[4]);
            $payment->setSenderAccountNumber($row[5]);
            $payment->setReceiverAccountNumber($row[6]);
            $payment->setSenderName($row[7]);
            $payment->setReceiverName($row[8]);

            $year = substr($row[1], 0, 4);
            $month = substr($row[1], 4, 2);
            $day = substr($row[1], 6, 2);

            $date = new \DateTime();
            $date->setDate($year, $month, $day);
            $date->setTime(0, 0, 0);

            $payment->setDate($date);
            $payment->setData('"' . implode($row, '","') . '"');

            $payment->setFilename($this->filename);
            $payment->setCode(PaymentImporterModel::BANK_TYPE_SANTANDER);

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