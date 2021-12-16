<?php

namespace GCRM\CRMBundle\Service;

class Balance
{
    private $toPay;

    private $paid;

    private $initialBalance = 0;

    /**
     * @return mixed
     */
    public function getInitialBalance()
    {
        return $this->initialBalance;
    }

    /**
     * @param mixed $initialBalance
     */
    public function setInitialBalance($initialBalance)
    {
        if ($initialBalance !== null && is_numeric($initialBalance)) {
            $this->initialBalance = number_format($initialBalance, 2, '.', '');
        }
    }

    /**
     * @return mixed
     */
    public function getToPay()
    {
        $balance = 0;
        if ($this->toPay > 0) {
            $balance = str_replace(',', '', number_format($this->toPay, 2, '.', ''));
        }

        return $balance;
    }

    /**
     * @param mixed $toPay
     */
    public function setToPay($toPay)
    {
        $this->toPay = $toPay;
    }

    /**
     * @return mixed
     */
    public function getPaid()
    {
        $balance = $this->getInitialBalance();
        if ($this->paid > 0) {
            $balance = str_replace(',', '', number_format($this->paid + $this->getInitialBalance(), 2, '.', ''));
        }

        return $balance;
    }

    /**
     * @param mixed $paid
     */
    public function setPaid($paid)
    {
        $this->paid = $paid;
    }

    public function getBalance()
    {
        return str_replace(',', '', $this->getToPay() - $this->getPaid());
    }
}