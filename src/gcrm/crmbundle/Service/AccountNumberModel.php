<?php

namespace GCRM\CRMBundle\Service;

class AccountNumberModel
{
    public function generateBankAccountNumber($bankStaticPartFromAccountNumber, $badgeId, $countryCode = 'PL')
    {
        $sumControl = $this->sumControl($countryCode . '00', $bankStaticPartFromAccountNumber, $badgeId);

        $accountNumber = $sumControl . $bankStaticPartFromAccountNumber . $badgeId;
        if (!$this->isValidBankAccountNumber($accountNumber)) {
            throw new \Exception('Bank account number is not valid');
        }

        return $accountNumber;
    }

    public function isValidBankAccountNumber($bankAccountNumber, $countryCode = 'PL')
    {
        $bankAccountNumber = $countryCode . $bankAccountNumber;
        if (mb_strlen($bankAccountNumber) != 28) {
            throw new \Exception();
        }

        $first4 = mb_substr($bankAccountNumber, 0, 4);
        $last24 = mb_substr($bankAccountNumber, 4);

        $bankAccountNumber = $this->replaceCharsToNumbers($last24 . $first4);
        $result = bcmod($bankAccountNumber, 97);

        if ($result != 1) {
            return false;
        }

        return true;
    }

    private function sumControl($first4, $static, $number)
    {
        $first4 = $this->replaceCharsToNumbers($first4);
        $changed = $number . $first4;

        $final = $static . $changed;
        $result = 98 - bcmod($final, 97);
        return mb_strlen($result) == 1 ? 0 . $result : $result;
    }

    private function replaceCharsToNumbers($chars)
    {
        $chars = str_replace('P', 25, $chars);
        $chars = str_replace('L', 21, $chars);

        return $chars;
    }
}