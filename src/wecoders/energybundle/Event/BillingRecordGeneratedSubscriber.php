<?php

namespace Wecoders\EnergyBundle\Event;

use Doctrine\ORM\EntityManagerInterface;
use GCRM\CRMBundle\Service\BillingDocument\Initializer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Wecoders\EnergyBundle\Entity\DocumentBankAccountChange;
use Wecoders\EnergyBundle\Entity\IsDocumentReadyForBankAccountChangeInterface;
use Wecoders\EnergyBundle\Service\DocumentBankAccountChangeModel;

class BillingRecordGeneratedSubscriber implements EventSubscriberInterface
{
    private $documentBankAccountChangeModel;

    private $em;

    private $initializer;

    public function __construct(
        DocumentBankAccountChangeModel $documentBankAccountChangeModel,
        EntityManagerInterface $em,
        Initializer $initializer
    )
    {
        $this->documentBankAccountChangeModel = $documentBankAccountChangeModel;
        $this->em = $em;
        $this->initializer = $initializer;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'billing_record.post_persist_single_document_actions' => [
                ['updateDocumentBankAccountChange'],
            ],
            'billing_record.post_persist' => [
                ['updateDocumentBankAccountChange'],
                ['updateDocumentsPaidState'],
            ],
            'billing_record.removed' => [
                ['removeDocumentNumberFromDocumentBankAccountChange'],
            ]
        ];
    }

    /**
     * @param BillingRecordGeneratedEvent $event
     */
    public function updateDocumentsPaidState(BillingRecordGeneratedEvent $event)
    {
        /** @var IsDocumentReadyForBankAccountChangeInterface $billingRecord */
        $billingRecord = $event->getBillingRecord();

        if ($billingRecord->getClient()) {
            $billingDocumentsObject = $this->initializer->init($billingRecord->getClient())->generate();
            $billingDocumentsObject->updateDocumentsIsPaidState();
        }
    }

    /**
     * @param BillingRecordGeneratedEvent $event
     */
    public function updateDocumentBankAccountChange(BillingRecordGeneratedEvent $event)
    {
        /** @var IsDocumentReadyForBankAccountChangeInterface $billingRecord */
        $billingRecord = $event->getBillingRecord();

        /** @var DocumentBankAccountChange $documentBankAccountChange */
        $documentBankAccountChange = $this->documentBankAccountChangeModel->getRecordByBadgeId($billingRecord->getBadgeId());
        if ($documentBankAccountChange && !$documentBankAccountChange->getDocumentNumber()) {
            $documentBankAccountChange->setDocumentNumber($billingRecord->getNumber());
            $this->em->persist($documentBankAccountChange);
            $this->em->flush($documentBankAccountChange);
        }
    }

    /**
     * @param BillingRecordGeneratedEvent $event
     */
    public function removeDocumentNumberFromDocumentBankAccountChange(BillingRecordGeneratedEvent $event)
    {
        /** @var IsDocumentReadyForBankAccountChangeInterface $billingRecord */
        $billingRecord = $event->getBillingRecord();
        if (!$billingRecord instanceof IsDocumentReadyForBankAccountChangeInterface) {
            throw new \InvalidArgumentException();
        }

        /** @var DocumentBankAccountChange $documentBankAccountChange */
        $documentBankAccountChange = $this->documentBankAccountChangeModel->getRecordByBadgeId($billingRecord->getBadgeId());
        if (
            $documentBankAccountChange &&
            $documentBankAccountChange->getDocumentNumber() &&
            $documentBankAccountChange->getDocumentNumber() == $billingRecord->getNumber()
        ) {
            $documentBankAccountChange->setDocumentNumber(null);
            $this->em->persist($documentBankAccountChange);
            $this->em->flush($documentBankAccountChange);
        }
    }

}