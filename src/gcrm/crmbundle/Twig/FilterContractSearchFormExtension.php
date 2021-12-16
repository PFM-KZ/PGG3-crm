<?php

namespace GCRM\CRMBundle\Twig;

use GCRM\CRMBundle\Entity\ContractInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig_Extension;

class FilterContractSearchFormExtension extends Twig_Extension
{
    private $request;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('filterContractSearchForm', array($this, 'filterContractSearchForm')),
        );
    }

    public function filterContractSearchForm($contract)
    {
        die('disabled functionality');
        if (!$contract) {
            return null;
        }

        if ($this->request->query->get('lsAgent')) {
            if (method_exists($contract, 'getAgent')) {
                $agent = $contract->getAgent();
                if (is_string($agent)) {
                    if (mb_strpos($agent, $this->request->query->get('lsAgent')) !== false) {
                        return $contract;
                    }
                }
            }

            return null;
        }

        if ($this->request->query->get('lsApplySignDate')) {
            if ($this->request->query->get('lsSignDateFrom')) {
                if (method_exists($contract, 'getSignDate')) {
                    $date = $this->request->query->get('lsSignDateFrom');
                    $dateTime = new \DateTime();
                    $dateTime->setDate($date['year'], $date['month'], $date['day']);
                    $dateTime->setTime(0, 0, 0);

                    if ($contract->getSignDate() && $contract->getSignDate() >= $dateTime) {
                        return $contract;
                    }
                }

                return null;
            }

            if ($this->request->query->get('lsSignDateTo')) {
                if (method_exists($contract, 'getSignDate')) {
                    $date = $this->request->query->get('lsSignDateTo');
                    $dateTime = new \DateTime();
                    $dateTime->setDate($date['year'], $date['month'], $date['day']);
                    $dateTime->setTime(0, 0, 0);

                    if ($contract->getSignDate() && $contract->getSignDate() <= $dateTime) {
                        return $contract;
                    }
                }

                return null;
            }
        }

        if ($this->request->query->get('lsApplyCreatedDate')) {
            if ($this->request->query->get('lsCreatedDateFrom')) {
                if (method_exists($contract, 'getCreatedAt')) {
                    $date = $this->request->query->get('lsCreatedDateFrom');
                    $dateTime = new \DateTime();
                    $dateTime->setDate($date['year'], $date['month'], $date['day']);
                    $dateTime->setTime(0, 0, 0);

                    if ($contract->getCreatedAt() && $contract->getCreatedAt() >= $dateTime) {
                        return $contract;
                    }
                }

                return null;
            }

            if ($this->request->query->get('lsCreatedDateTo')) {
                if (method_exists($contract, 'getCreatedAt')) {
                    $date = $this->request->query->get('lsCreatedDateTo');
                    $dateTime = new \DateTime();
                    $dateTime->setDate($date['year'], $date['month'], $date['day']);
                    $dateTime->setTime(0, 0, 0);

                    if ($contract->getCreatedAt() && $contract->getCreatedAt() <= $dateTime) {
                        return $contract;
                    }
                }

                return null;
            }
        }

        return $contract;
    }
}