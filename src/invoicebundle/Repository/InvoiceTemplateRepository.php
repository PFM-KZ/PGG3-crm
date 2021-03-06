<?php

namespace Wecoders\InvoiceBundle\Repository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping;

/**
 * InvoiceTemplateRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class InvoiceTemplateRepository extends \Doctrine\ORM\EntityRepository
{
    public static $em;

    public function __construct(EntityManager $em, Mapping\ClassMetadata $class)
    {
        self::$em = $em;
        parent::__construct($em, $class);
    }

    public static function documentTemplateRecordsForDebitNote()
    {
        $debitNoteTemplateId = 15;

        $qb = self::$em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from('WecodersInvoiceBundle:InvoiceTemplate', 'a')
            ->where('a.id = ' . $debitNoteTemplateId)
        ;
        return $q;
    }
}
