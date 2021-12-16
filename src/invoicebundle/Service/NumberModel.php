<?php

namespace Wecoders\InvoiceBundle\Service;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Wecoders\InvoiceBundle\Entity\InvoiceNumberSettings;

class NumberModel
{
    const TOKEN_ID = '#id#';
    const TOKEN_AI = '#ai#';
    const TOKEN_MONTH = '#mm#';
    const TOKEN_YEAR = '#yyyy#';
    const TOKEN_AI_BY_ID = '#aibyid#';

    private $kernelRootDir;

    /** @var  EntityManager */
    private $em;

    private $aiTokenIndex;
    private $monthTokenIndex;
    private $yearTokenIndex;
    private $aibyidTokenIndex;
    private $idTokenIndex;
    private $createdDate;

    public function init($kernelRootDir, EntityManager $em, \DateTime $createdDate)
    {
        $this->em = $em;
        $this->kernelRootDir = $kernelRootDir;
        $this->createdDate = $createdDate;
    }

    public function generate($tokensWithReplacement = [], $aiTableToCheck, $aiFieldWithInvoiceNumber, $typeByCode)
    {
        /** @var InvoiceNumberSettings $settings */
        $settings = $this->getSettings($typeByCode);
        if (!$settings) {
            return null;
        }

        // sets initial data
        $structure = $settings->getStructure();
        $this->aiTokenIndex = $this->getTokenIndex($structure, self::TOKEN_AI);
        $this->aibyidTokenIndex = $this->getTokenIndex($structure, self::TOKEN_AI_BY_ID);
        $this->monthTokenIndex = $this->getTokenIndex($structure, self::TOKEN_MONTH);
        $this->yearTokenIndex = $this->getTokenIndex($structure, self::TOKEN_YEAR);
        $this->idTokenIndex = $this->getTokenIndex($structure, self::TOKEN_ID);


        // month token
        if ($this->monthTokenIndex !== null) {
            $month = $this->createdDate->format('m');
            $month = $this->manageLeadingZerosFormat($month, $settings->getLeadingZeros());
            $structure = str_replace(self::TOKEN_MONTH, $month, $structure);
        }


        // year token
        if ($this->yearTokenIndex !== null) {
            $structure = str_replace(self::TOKEN_YEAR, $this->createdDate->format('Y'), $structure);
        }


        // ai token
        if ($this->aiTokenIndex !== null) {
            $invoiceRecords = $this->getInvoiceRecordsWithSameStructure($settings->getStructure(), $aiTableToCheck, $aiFieldWithInvoiceNumber);
            $invoiceNumbers = $this->getArrayValues($invoiceRecords);

            // if there is no record in database, so system cannot retrieve ai number
            $newAiNumber = 1;
            if (!$settings->getExcludeAiFromLeadingZeros()) {
                $newAiNumber = $this->manageLeadingZerosFormat($newAiNumber, $settings->getLeadingZeros());
            }

            // if there is already record in database with this structure
            if (count($invoiceNumbers)) {
                // check for month and year and number and get higest number
                $lastAiNumber = 0;
                if ($settings->getResetAiAtNewMonth()) {
                    $invoiceNumbers = $this->filterNumbersByMonthAndYear($invoiceNumbers);
                    if (count($invoiceNumbers)) {
                        $lastAiNumber = $this->getLastAiNumberResetNumberOption($invoiceNumbers, $this->aiTokenIndex);
                    }
                } else {
                    $lastAiNumber = $this->getLastAiNumberNoResetNumberOption($invoiceNumbers, $this->aiTokenIndex);
                }

                $newAiNumber = ++$lastAiNumber;

                if (!$settings->getExcludeAiFromLeadingZeros()) {
                    // sets format with leading zeros or not
                    $newAiNumber = $this->manageLeadingZerosFormat($newAiNumber, $settings->getLeadingZeros());
                }
            }
            $structure = str_replace(self::TOKEN_AI, $newAiNumber, $structure);
        }




        // aibyid token
        if ($this->aibyidTokenIndex !== null) {
            $idReplacement = $this->getIdTokenReplacementFromAdditionalTokens($tokensWithReplacement);
            // token cant get replacement ID
            if ($idReplacement) {
                $invoiceRecords = $this->getInvoiceRecordsWithSameStructure($settings->getStructure(), $aiTableToCheck, $aiFieldWithInvoiceNumber);
                $invoiceRecords = $this->filterByMatchedIdToken($invoiceRecords, $aiFieldWithInvoiceNumber, $idReplacement);
                $invoiceNumbers = $this->getArrayValues($invoiceRecords);

                // if there is no record in database, so system cannot retrieve ai number
                $newAiNumber = $this->manageLeadingZerosFormat(1, $settings->getLeadingZeros());
                // if there is already record in database with this structure
                if (count($invoiceNumbers)) {
                    // check for month and year and number and get higest number
                    $lastAiNumber = 0;
                    if ($settings->getResetAiAtNewMonth()) {
                        $invoiceNumbers = $this->filterNumbersByMonthAndYear($invoiceNumbers);
                        if (count($invoiceNumbers)) {
                            $lastAiNumber = $this->getLastAiNumberResetNumberOption($invoiceNumbers, $this->aibyidTokenIndex);
                        }
                    } else {
                        $lastAiNumber = $this->getLastAiNumberNoResetNumberOption($invoiceNumbers, $this->aibyidTokenIndex);
                    }

                    $newAiNumber = ++$lastAiNumber;

                    // sets format with leading zeros or not
                    $newAiNumber = $this->manageLeadingZerosFormat($newAiNumber, $settings->getLeadingZeros());
                }
                $structure = str_replace(self::TOKEN_AI_BY_ID, $newAiNumber, $structure);
            }
        }




        // additional tokens
        foreach ($tokensWithReplacement as $tokenWithReplacement) {
            if (isset($tokenWithReplacement['token']) && $tokenWithReplacement['replacement']) {
                $structure = str_replace($tokenWithReplacement['token'], $tokenWithReplacement['replacement'], $structure);
            }
        }

        return $structure;
    }

    private function getIdTokenReplacementFromAdditionalTokens($tokensWithReplacement)
    {
        foreach ($tokensWithReplacement as $tokenWithReplacement) {
            if (isset($tokenWithReplacement['token']) && $tokenWithReplacement['token'] == self::TOKEN_ID) {
                return $tokenWithReplacement['replacement'];
            }
        }
        return null;
    }

    private function manageLeadingZerosFormat($value, $isLeadingZeros)
    {
        $value = (int) $value;
        if (strlen($value) == 1 && $isLeadingZeros) {
            $value = '0' . $value;
        }

        return $value;
    }

    private function getLastAiNumberResetNumberOption($invoiceNumbers, $tokenAiFamilyIndex)
    {
        $tmpDates = [];
        foreach ($invoiceNumbers as $invoiceNumber) {
            $splitted = explode('/', $invoiceNumber);

            $month = $splitted[$this->monthTokenIndex];
            $year = $splitted[$this->yearTokenIndex];

            // changes to datetime to easy get last date (month / year)
            // only from last date (month / year) records will be checked to fetch last number for ai
            // sets first day of month but after that it will be excluded
            $now = new \DateTime();
            $date = $now->setDate($year, $month, 1);
            $date->setTime(0, 0, 0);
            $tmpDates[] = $date;
        }

        // gets higher date
        $date = null;
        foreach ($tmpDates as $tmpDate) {
            if ($date === null || $tmpDate > $date) {
                $date = $tmpDate;
            }
        }

        // sets higher month and year
        $month = null;
        $year = null;
        /** @var \DateTime $date */
        if ($date) {
            $month = $date->format('m');
            $year = $date->format('Y');
        }

        // if createdDate is higher than last added records (month / year) then start numeration from 1
        $createdDateMonth = $this->createdDate->format('m');
        $createdDateYear = $this->createdDate->format('Y');
        if (
            ($createdDateMonth > $month && $createdDateYear >= $year) ||
            $createdDateYear > $year
        ) {
            $maxNumber = 0;
        } else {
            $maxNumber = 0;
            foreach ($invoiceNumbers as $invoiceNumber) {
                $splitted = explode('/', $invoiceNumber);

                $tmpMonth = $splitted[$this->monthTokenIndex];
                $tmpYear = $splitted[$this->yearTokenIndex];

                // gets latest invoices records
                if ($month == $tmpMonth && $year == $tmpYear) {
                    $ai = $splitted[$tokenAiFamilyIndex];
                    $maxNumber = max($maxNumber, (int) $ai);
                }
            }
        }

        return $maxNumber;
    }

    private function getLastAiNumberNoResetNumberOption($invoiceNumbers, $tokenAiFamilyIndex)
    {
        $maxNumber = 0;
        foreach ($invoiceNumbers as $invoiceNumber) {
            $splitted = explode('/', $invoiceNumber);

            $ai = $splitted[$tokenAiFamilyIndex];
            $maxNumber = max($maxNumber, (int) $ai);
        }

        return $maxNumber;
    }

    private function getTokenIndex($structure, $token)
    {
        $pieces = explode('/', $structure);
        for ($i = 0; $i < count($pieces); $i++) {
            if ($pieces[$i] == $token) {
                return $i;
            }
        }
        return null;
    }

    public function getTokenValue($numberToSearchIn, $structure, $token)
    {
        $index = $this->getTokenIndex($structure, $token);
        $pieces = explode('/', $numberToSearchIn);
        return $pieces[$index];
    }

    private function getArrayValues($records)
    {
        $result = [];
        if ($records) {
            foreach ($records as $record) {
                $result[] = array_values($record)[0];
            }
        }

        return $result;
    }

    public function getSettings($typeByCode)
    {
        return $this->em->getRepository('WecodersInvoiceBundle:InvoiceNumberSettings')->findOneBy([
            'code' => $typeByCode
        ]);
    }

    public function getInvoiceRecordsWithSameStructure($structureToCheckFor, $tableToCheck, $fieldWithInvoiceNumber)
    {
        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['a.' . $fieldWithInvoiceNumber])
            ->from($tableToCheck, 'a')
            ->where('a.numberStructure = :structure')
            ->setParameters([
                'structure' => $structureToCheckFor
            ])
            ->getQuery()
        ;

        return $q->getResult();
    }

    public function filterByMatchedIdToken($records, $aiFieldWithInvoiceNumber, $idToSearchFor)
    {
        $result = [];

        if ($records) {
            foreach ($records as $record) {
                $pieces = explode('/', $record[$aiFieldWithInvoiceNumber]);
                if ($pieces[$this->idTokenIndex] == $idToSearchFor) {
                    $result[] = $record;
                }
            }
        }

        if (count($result)) {
            return $result;
        }

        return null;
    }

    public function filterNumbersByMonthAndYear($numbers)
    {
        $year = $this->createdDate->format('Y');
        $month = $this->createdDate->format('m');

        $result = [];

        if ($numbers) {
            foreach ($numbers as $number) {
                $pieces = explode('/', $number);
                if ($pieces[$this->yearTokenIndex] == $year && $pieces[$this->monthTokenIndex] == $month) {
                    $result[] = $number;
                }
            }
        }

        return $result;
    }
}