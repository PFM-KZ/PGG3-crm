<?php

namespace Wecoders\EnergyBundle\Service;

use GCRM\CRMBundle\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use GCRM\CRMBundle\Entity\Settings;
use Wecoders\EnergyBundle\Entity\InvoiceBase;
use Wecoders\EnergyBundle\Entity\SmsMessage;
use Wecoders\EnergyBundle\Entity\SmsTemplate;
use Wecoders\EnergyBundle\Entity\SmsClientGroup;
use Wecoders\EnergyBundle\Entity\InvoiceProforma;
use TZiebura\SmsBundle\Interfaces\SmsSenderInterface;
use GCRM\CRMBundle\Service\BillingDocument\Initializer;
use Wecoders\EnergyBundle\Entity\InvoiceSettlement;
use Wecoders\EnergyBundle\Repository\SmsTemplateRepository;

class SmsClientGroupModel
{
    const SETTING_FROM = 'sms_from';
    const SETTING_API_KEY = 'smsapi_api_key';

    private $settingBatchSize = 'sms_batch_size';
    private $settingGroupCreationTime = 'sms_group_creation_time';
    private $settingSmsSendingTime = 'sms_sending_time';

    /** @var int $defaultBatchSize */
    private $defaultBatchSize = 150;

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var SmsSenderInterface $sender */
    private $sender;

    /** @var Initializer $initializer */
    private $initializer;

    private $smsTemplateRepository;

    function __construct(EntityManagerInterface $em, SmsSenderInterface $sender, Initializer $initializer, SmsTemplateRepository $smsTemplateRepository)
    {
        $this->em = $em;
        $this->sender = $sender;
        $this->initializer = $initializer;
        $this->smsTemplateRepository = $smsTemplateRepository;
    }

    public function isPrePaymentGroupCreated()
    {
        return $this->isGroupCreatedToday(SmsClientGroup::GROUP_PRE_PAYMENT_SMS);
    }

    public function isPostPaymentGroupCreated()
    {
        return $this->isGroupCreatedToday(SmsClientGroup::GROUP_POST_PAYMENT_SMS);
    }

    private function isGroupCreatedToday($code)
    {
        $qb = $this->em->createQueryBuilder();
        $smsGroup = $qb
            ->select('scg')
            ->from('Wecoders\EnergyBundle\Entity\SmsClientGroup', 'scg')
            ->where(
                'scg.createdAt >= :todayMidnight',
                'scg.createdAt < :tomorrowMidnight',
                'scg.code = :code'
            )
            ->setParameters($this->createRange())
            ->setParameter('code', $code)
            ->getQuery()
            ->getResult()
        ;

        if(!$smsGroup || !count($smsGroup)) {
            return false;
        }

        return true;
    }

    private function createRange()
    {
        $todayMidnight = (new \DateTime())->setTime(0,0);
        $tomorrowMidnight = (clone $todayMidnight)->modify('+1days');

        return [
            'todayMidnight' => $todayMidnight,
            'tomorrowMidnight' => $tomorrowMidnight,
        ];
    }

    public function createPrePaymentGroup()
    {
        $today = new \DateTime();
        $daysToSkip = [5, 6];

        $weekDay = $today->format('N');

        
//        if (array_search($weekDay, $daysToSkip) !== false) {
//            return;
//        }

        $groupCreationTime = $this->em->getRepository('GCRM\CRMBundle\Entity\Settings')->findOneBy(['name' => $this->settingGroupCreationTime]);
        if ($groupCreationTime && $groupCreationTime->getContent()) {
            $groupCreationTime = $groupCreationTime->getContent();
            $hourMinute = explode(':', $groupCreationTime);
            $creationTime = (clone $today)->setTime($hourMinute[0], $hourMinute[1]);

            if($today < $creationTime) {
                return;
            }
        }

        if ($this->isPrePaymentGroupCreated()) {
            return;
        }

        $paymentDates = [];
        if ($weekDay == 7) {
            $paymentDates[] = (clone $today)->modify('-1days')->setTime(0,0);
            $paymentDates[] = (clone $today)->setTime(0,0);
        } else {
            $paymentDates[] = (clone $today)->modify('+1days')->setTime(0,0);
        }

        $clients = $this->getClientsWithDocuments($paymentDates);

        if (!count($clients)) {
            return;
        }



        $template = $this->getTemplateByCode(SmsTemplate::PRE_PAYMENT_TEMPLATE);

        $smsGroup = new SmsClientGroup();
        $smsGroup
            ->setTitle('Dzień przed terminem(' . $today->format('d-m-Y') . ')')
            ->setStatusCode(SmsClientGroup::STATUS_AWAITING)
            ->setSmsTemplate($template)
            ->setCode(SmsClientGroup::GROUP_PRE_PAYMENT_SMS)
        ;

        $this->addClientsToGroup($smsGroup, $clients, $paymentDates);
        $this->persistGroup($smsGroup);
    }

    public function createPostPaymentGroup()
    {
        $today = new \DateTime();
        $daysToSkip = [1,7];

        $weekDay = $today->format('N');

//        if (array_search($weekDay, $daysToSkip) !== false) {
//            return;
//        }

        $groupCreationTime = $this->em
            ->getRepository('GCRM\CRMBundle\Entity\Settings')
            ->findOneBy([
                'name' => $this->settingGroupCreationTime
            ])
        ;

        if ($groupCreationTime && $groupCreationTime->getContent()) {
            $groupCreationTime = $groupCreationTime->getContent();
            $hourMinute = explode(':', $groupCreationTime);
            $creationTime = (clone $today)->setTime($hourMinute[0], $hourMinute[1]);

            if($today < $creationTime) {
                return;
            }
        }

        if ($this->isPostPaymentGroupCreated()) {
            return;
        }

        $paymentDates = [];
        if ($weekDay == 2) {
            $paymentDates[] = (clone $today)->modify('-1days')->setTime(0,0);
            $paymentDates[] = (clone $today)->modify('-2days')->setTime(0,0);
            $paymentDates[] = (clone $today)->modify('-3days')->setTime(0,0);
        } else {
            $paymentDates[] = (clone $today)->modify('-1days')->setTime(0,0);
        }

        $clients = $this->getClientsWithDocuments($paymentDates);

        if (!count($clients)) {
            return;
        }

        $template = $this->getTemplateByCode(SmsTemplate::POST_PAYMENT_TEMPLATE);
    
        $smsGroup = new SmsClientGroup();
        $smsGroup
            ->setTitle('Dzień po terminie(' . $today->format('d-m-Y') . ')')
            ->setStatusCode(SmsClientGroup::STATUS_AWAITING)
            ->setSmsTemplate($template)
            ->setCode(SmsClientGroup::GROUP_POST_PAYMENT_SMS)
        ;

        $this->addClientsToGroup($smsGroup, $clients, $paymentDates);
        $this->persistGroup($smsGroup);
    }

    private function addClientsToGroup(SmsClientGroup $smsClientGroup, array $clients, array $paymentDates)
    {
        /** @var Client $client */
        foreach($clients as $client) {
            $documents = $this->getClientActualDocuments($client, $paymentDates);

            $documentsToPay = [];
            $totalGrossValue = 0;

            /** @var InvoiceBase $document */
            foreach($documents as $document) {
                if ($document->getSummaryGrossValue() == $document->getPaidValue()) {
                    continue;
                }

                $documentsToPay[] = $document->getNumber();
                $totalGrossValue += $document->getSummaryGrossValue() - $document->getPaidValue();
            }
            
            if ($totalGrossValue < 1 || count($documentsToPay) == 0) {
                continue;
            }

            $documentsText = implode(', ', $documentsToPay);
            $tokensAndValues = array(
                '{_dokumenty_}' => $documentsText,
                '{_kwota_}' => $totalGrossValue,
                '{_aktualny_nr_rachunku_bankowego_}' => $client->getBankAccountNumber()
            );

            
            $number = $client->getContactTelephoneNr() ? $client->getContactTelephoneNr() : $client->getTelephoneNr();
            $smsMessage = new SmsMessage();
            $smsMessage
                ->setClient($client)
                ->setNumber($number)
                ->setMessage($this->sender->replaceTokens($smsClientGroup->getSmsTemplate(), $tokensAndValues))
                ->setDocumentNumbers($documentsText)
            ;
            
            if (!$smsMessage->getNumber()) {
                $smsMessage->setNumber('');
                $smsMessage->setStatusCode(SmsMessage::STATUS_ERROR);
                $smsMessage->setErrorCode(500);
                $smsMessage->setErrorMessage('Numer nie został podany');
                $smsClientGroup->setErrorCount($smsClientGroup->getErrorCount() + 1);
            }

            $smsClientGroup->addSmsMessage($smsMessage);
        }
    }

    private function getClientActualDocuments(Client $client, $paymentDates)
    {
        $documentsStructure = $this->initializer->init($client)->generate()->getStructure();
        $documents = array();
        
        $dates = [];

        foreach($paymentDates as $date) {
            $dates[] = $date->format('Y-m-d');
        }
        foreach ($documentsStructure['data'] as $data) {

            foreach ($data['records'] as $document) {
                if ($document->getIsNotActual()) { // gets only actual documents
                    continue;
                }

                if (!$document->getNumber()) {
                    continue;
                }

                if (!$document->getSummaryGrossValue()) {
                    continue;
                }

                if ($document instanceof InvoiceProforma) {
                    if(count($document->getCorrections())) {
                        continue;
                    }
                }

                if ($document instanceof InvoiceSettlement) {
                    if(count($document->getCorrections())) {
                        continue;
                    }
                }

                if (
                    !$document->getIsPaid() &&
                    array_search($document->getDateOfPayment()->format('Y-m-d'), $dates) !== false
                ) {
                    $documents[] = $document;
                }
            }
        }

        return $documents;
    }

    private function getTemplateByCode($templateCode)
    {
        return $this->em->getRepository('Wecoders\EnergyBundle\Entity\SmsTemplate')->findOneBy(['code' => $templateCode]);
    }

    public function getClientsWithDocuments(array $paymentDates)
    {
        $qb = $this->em->createQueryBuilder();

        $dates = [];

        foreach($paymentDates as $date) {
            $dates[] = $date->format('Y-m-d');
        }
        return $qb
            ->select('c')
            ->from('GCRM\CRMBundle\Entity\Client', 'c')
            ->leftJoin('Wecoders\EnergyBundle\Entity\InvoiceProforma', 'ip', 'WITH', 'ip.client = c.id')
            ->leftJoin('Wecoders\EnergyBundle\Entity\InvoiceProformaCorrection', 'ipc', 'WITH', 'ipc.client = c.id')
            ->leftJoin('Wecoders\EnergyBundle\Entity\InvoiceSettlement', 'iset', 'WITH', 'iset.client = c.id')
            ->leftJoin('Wecoders\EnergyBundle\Entity\InvoiceSettlementCorrection', 'isetc', 'WITH', 'isetc.client = c.id')
            ->where('ip.isPaid = 0 AND ip.dateOfPayment IN (:paymentDates)')
            ->orWhere('ipc.isPaid = 0 AND ipc.dateOfPayment IN (:paymentDates)')
            ->orWhere('iset.isPaid = 0 AND iset.dateOfPayment IN (:paymentDates)')
            ->orWhere('isetc.isPaid = 0 AND isetc.dateOfPayment IN (:paymentDates)')
            ->setParameter('paymentDates', $dates)
            ->getQuery()
            ->getResult()
        ;
    }

    private function persistGroup(SmsClientGroup $smsClientGroup)
    {
        $this->em->persist($smsClientGroup);
        $this->em->flush();
    }

    private function updateGroup(SmsClientGroup $smsClientGroup)
    {
        $this->em->flush($smsClientGroup);
    }

    public function processGroup()
    {
        $sendingTime = $this->em->getRepository('GCRM\CRMBundle\Entity\Settings')->findOneBy(['name' => $this->settingSmsSendingTime ]);

        if($sendingTime && $sendingTime->getContent()) {
            $sendingTime = $sendingTime->getContent();
            $today = new \DateTime();
            $exploded = explode(':',$sendingTime);
            $sendingTime = (clone $today)->setTime($exploded[0], $exploded[1]);

            if($today < $sendingTime) {
                return;
            }
        }

        /** @var Settings $fromSetting */
        $fromSetting = $this->em->getRepository('GCRM\CRMBundle\Entity\Settings')->findOneBy(['name' => self::SETTING_FROM]);
        if (!$fromSetting || !$fromSetting->getContent()) {
            throw new \RuntimeException('Settings parameter ' . self::SETTING_FROM . ' is not set');
        }
        $this->sender->setFrom($fromSetting->getContent());


        $smsApiKey = $this->em->getRepository('GCRM\CRMBundle\Entity\Settings')->findOneBy(['name' => self::SETTING_API_KEY]);
        $smsApiKey = $smsApiKey->getContent();
        $this->sender->createService($smsApiKey);
        
        /** @var SmsClientGroup $group */
        $group = $this->findGroupToProcess();

        if(!$group) {
            return;
        }

        $success = 0;
        $error = 0;

        $smsMessages = $this->findGroupMessages($group);
        
        
        /** @var SmsMessage $smsMessage */
        foreach($smsMessages as &$smsMessage) {

            $this->sender->sendSms($smsMessage);

            if($smsMessage->getStatusCode() === SmsMessage::STATUS_SUCCESS) {
                $success++;
            }

            if($smsMessage->getStatusCode() === SmsMessage::STATUS_ERROR) {
                $error++;
            }

            $this->em->flush($smsMessage);
        }

        $group->setSentCount($group->getSentCount() + $success);
        $group->setErrorCount($group->getErrorCount() + $error);

        if(($group->getSentCount() + $group->getErrorCount()) >= $group->getTotalToSendCount()) {
            $group->setStatusCode(SmsClientGroup::STATUS_COMPLETED);
            $group->setCompletedAt(new \DateTime());
        }

        $this->updateGroup($group);
    }

    public function findGroupMessages(SmsClientGroup $smsClientGroup)
    {
        $batchSize = $this->em->getRepository('GCRM\CRMBundle\Entity\Settings')->findOneBy(['name' => $this->settingBatchSize]);

        if($batchSize && $batchSize->getContent()) {
            $limit = $batchSize->getContent();
        } else {
            $limit = $this->defaultBatchSize;
        }

        return $this->em->getRepository('Wecoders\EnergyBundle\Entity\SmsMessage')->findBy([
            'smsClientGroup' => $smsClientGroup,
            'statusCode' => SmsMessage::STATUS_AWAITING
        ], ['id' => 'ASC'], $limit);
    }

    public function findGroupToProcess()
    {
        $repository = $this->em->getRepository('Wecoders\EnergyBundle\Entity\SmsClientGroup');

        $group = $repository->findOneBy(['statusCode' => SmsClientGroup::STATUS_PROCESSING, 'isSuspended' => false]);

        if($group) {
            return $group;
        }

        $groups = $repository->findBy(['statusCode' => SmsClientGroup::STATUS_AWAITING, 'isSuspended' => false], ['id' => 'ASC']);

        if($groups && count($groups)) {
            /** @var SmsClientGroup $group */
            $group = $groups[0];

            $group->setStatusCode(SmsClientGroup::STATUS_PROCESSING);

            $this->em->flush($group);

            return $group;
        }
        
        return null;
    }

    public function createSmsClientGroupWithCustomPaymentDate($title, array $datesOfPayment)
    {
        $clients = $this->getClientsWithDocuments($datesOfPayment);
        $smsClientGroup = new SmsClientGroup();
        $template = $this->smsTemplateRepository->findOneBy(['code' => SmsTemplate::CUSTOM_DATE_OF_PAYMENT_TEMPLATE]);

        if (!$template) {
            $template = new SmsTemplate();
            $template->setCode(SmsTemplate::CUSTOM_DATE_OF_PAYMENT_TEMPLATE)
                ->setTitle('Szablon z dynamiczną datą płatności')
                ->setMessage('W dniu {_data_} minął termin platnosci kwoty {_kwota_}PLN wynikajacy z faktury {_dokumenty_}. Prosimy o uregulowanie naleznosci w terminie.');
            $this->em->persist($template);
            $this->em->flush();
        }

        $smsClientGroup->setSmsTemplate($template);
        $this->addClientsToGroup($smsClientGroup, $clients, $datesOfPayment);

        foreach($smsClientGroup->getSmsMessages() as $smsMessage) {
            $message = $smsMessage->getMessage();
            $message = str_replace('{_data_}', $datesOfPayment[0]->format('d.m.Y'), $message);
            $smsMessage->setMessage($message);
        }

        $smsClientGroup->setIsSuspended(true);
        $smsClientGroup->setStatusCode(SmsClientGroup::STATUS_SUSPENDED);
        $smsClientGroup->setCode(SmsClientGroup::GROUP_CUSTOM_DATE_OF_PAYMENT_SMS);
        $smsClientGroup->setTitle($title);

        $this->em->persist($smsClientGroup);
        $this->em->flush();
    }
}
