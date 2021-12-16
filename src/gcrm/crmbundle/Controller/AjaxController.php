<?php

namespace GCRM\CRMBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\Distributor;
use GCRM\CRMBundle\Entity\DistributorBranch;
use GCRM\CRMBundle\Service\DistributorBranchModel;
use GCRM\CRMBundle\Service\DistributorModel;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Wecoders\EnergyBundle\Entity\Tariff;

class AjaxController extends Controller
{
    /**
     * @Route("/fetch-client-data", name="fetchClientData", methods={"POST"})
     */
    public function selectAction(Request $request, EntityManagerInterface $em)
    {
        $search = $request->request->get('search');

        $qb = $em->createQueryBuilder();
        $q = $qb->select('a')
            ->from('GCRMCRMBundle:Client', 'a')
            ->where("
                a.name LIKE :search OR
                a.surname LIKE :search OR
                a.pesel LIKE :search OR
                a.nip LIKE :search OR
                a.telephoneNr LIKE :search OR
                ani.number LIKE :search OR
                cg.contractNumber LIKE :search OR
                ce.contractNumber LIKE :search OR
                CONCAT (a.name, ' ', a.surname) LIKE :search
            ")
            ->leftJoin('GCRMCRMBundle:AccountNumberIdentifier', 'ani', 'WITH', 'a.accountNumberIdentifier = ani.id')
            ->leftJoin('GCRMCRMBundle:ClientAndContractGas', 'cacg', 'WITH', 'cacg.client = a.id')
            ->leftJoin('GCRMCRMBundle:ContractGas', 'cg', 'WITH', 'cacg.contract = cg.id')
            ->leftJoin('GCRMCRMBundle:ClientAndContractEnergy', 'cace', 'WITH', 'cace.client = a.id')
            ->leftJoin('GCRMCRMBundle:ContractEnergy', 'ce', 'WITH', 'cace.contract = ce.id')
            ->setParameters(['search' => '%' . $search . '%'])
            ->getQuery()
        ;

        $dbResult = $q->getResult();

        if ($dbResult && count($dbResult) > 100) {
            return new Response(json_encode(['tooMany' => count($dbResult)]));
        }

        // map to array
        /** @var Client $item */
        $result = [];
        foreach ($dbResult as $item) {
            $result[] = [
                'id' => $item->getId(),
                'text' => (string) $item,
            ];
        }

        return new Response(json_encode(['results' => $result]));
    }

    /**
     * @Route("/fetch-client-data-for-debit-note", name="fetchClientDataForDebitNote")
     */
    public function fetchClientDataForDebitNoteAction(Request $request, EntityManagerInterface $em)
    {
        $search = $request->request->get('search');
//        $search = $request->query->get('search');

        $qb = $em->createQueryBuilder();
        $q = $qb->select('a as client', '(CASE WHEN ce.contractNumber IS NOT NULL THEN ce.contractNumber ELSE cg.contractNumber END) as contractNumber')
            ->from('GCRMCRMBundle:Client', 'a')
            ->where('
                a.name LIKE :search OR
                a.surname LIKE :search OR
                a.pesel LIKE :search OR
                a.nip LIKE :search OR
                a.telephoneNr LIKE :search OR
                ani.number LIKE :search OR
                cg.contractNumber LIKE :search OR
                ce.contractNumber LIKE :search
            ')
            ->leftJoin('GCRMCRMBundle:AccountNumberIdentifier', 'ani', 'WITH', 'a.accountNumberIdentifier = ani.id')
            ->leftJoin('GCRMCRMBundle:ClientAndContractGas', 'cacg', 'WITH', 'cacg.client = a.id')
            ->leftJoin('GCRMCRMBundle:ContractGas', 'cg', 'WITH', 'cacg.contract = cg.id')
            ->leftJoin('GCRMCRMBundle:ClientAndContractEnergy', 'cace', 'WITH', 'cace.client = a.id')
            ->leftJoin('GCRMCRMBundle:ContractEnergy', 'ce', 'WITH', 'cace.contract = ce.id')
            ->setParameters(['search' => '%' . $search . '%'])
            ->getQuery()
        ;

        $dbResult = $q->getResult();

        if ($dbResult && count($dbResult) > 100) {
            return new Response(json_encode(['tooMany' => count($dbResult)]));
        }

        // map to array
        $result = [];
        foreach ($dbResult as $item) {
            /** @var Client $client */
            $client = $item['client'];

            $result[] = [
                'id' => $client->getId(),
                'text' => (string) $client,
                'map' => [
                    'debitnote_badgeId' => $client->getAccountNumberIdentifier()->getNumber(),
                    'debitnote_clientAccountNumber' => $client->getBankAccountNumber(),
                    'debitnote_clientName' => $client->getName(),
                    'debitnote_clientSurname' => $client->getSurname(),
                    'debitnote_clientZipCode' => $client->getToCorrespondenceZipCode(),
                    'debitnote_clientCity' => $client->getToCorrespondenceCity(),
                    'debitnote_clientStreet' => $client->getToCorrespondenceStreet(),
                    'debitnote_clientHouseNr' => $client->getToCorrespondenceHouseNr(),
                    'debitnote_clientApartmentNr' => $client->getToCorrespondenceApartmentNr(),
                    'debitnote_clientPostOffice' => $client->getToCorrespondencePostOffice(),
                    'debitnote_contractNumber' => $item['contractNumber'],
                ]
            ];
        }

        return new Response(json_encode(['results' => $result, 'select' => $request->request->get('select'), 'map' => true]));
    }

    /**
     * @Route("/fetch-distributor-branch-data", name="fetchDistributorBranchData", methods={"POST"})
     */
    public function fetchDistributorBranchDataAction(
        Request $request,
        DistributorModel $distributorModel,
        DistributorBranchModel $distributorBranchModel
    )
    {
        $id = $request->request->get('id');

        /** @var Distributor $distributor */
        $distributor = $distributorModel->getRecord($id);
        if (!$distributor) {
            return new Response(json_encode(['results' => []]));
        }

        $distributorBranches = $distributorBranchModel->getRecordsByDistributor($distributor);
        $result = [];

        /** @var DistributorBranch $item */
        foreach ($distributorBranches as $item) {
            $result[] = [
                'id' => $item->getId(),
                'text' => (string) $item
            ];
        }

        return new Response(json_encode(['results' => $result]));
    }

    /**
     * @Route("/fetch-tariff-data", name="fetchTariffData", methods={"POST"})
     */
    public function fetchTariffDataAction(
        Request $request,
        EntityManagerInterface $em
    )
    {
        $records = $em->getRepository(Tariff::class)->findBy(['energyType' => $request->request->get('id')]);
        $result = [];

        /** @var Tariff $item */
        foreach ($records as $item) {
            $result[] = [
                'id' => $item->getId(),
                'text' => (string) $item
            ];
        }

        return new Response(json_encode(['results' => $result]));
    }

    /**
     * @Route("/fetch-tariff-codes-for-energy-type", name="fetchTariffCodesForEnergyType", methods={"POST"})
     */
    public function fetchTariffCodesForEnergyType(
        Request $request,
        DistributorModel $distributorModel,
        DistributorBranchModel $distributorBranchModel
    )
    {
        $id = $request->request->get('id');

        /** @var Distributor $distributor */
        $distributor = $distributorModel->getRecord($id);
        if (!$distributor) {
            return new Response(json_encode(['results' => []]));
        }

        $distributorBranches = $distributorBranchModel->getRecordsByDistributor($distributor);
        $result = [];

        /** @var DistributorBranch $item */
        foreach ($distributorBranches as $item) {
            $result[] = [
                'id' => $item->getId(),
                'text' => (string) $item
            ];
        }

        return new Response(json_encode(['results' => $result]));
    }
}
