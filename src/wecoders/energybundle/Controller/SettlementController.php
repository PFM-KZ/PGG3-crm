<?php

namespace Wecoders\EnergyBundle\Controller;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\ContractEnergyBase;
use GCRM\CRMBundle\Service\BillingDocument\Initializer;
use GCRM\CRMBundle\Service\EasyAdminModel;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Wecoders\EnergyBundle\Entity\PriceList;
use Wecoders\EnergyBundle\Form\SettlementType;
use Wecoders\EnergyBundle\Service\ReadingsValidationException;
use Wecoders\EnergyBundle\Service\SettlementModel;

class SettlementController extends Controller
{
    /**
     * @Route("/energy-settlement-panel", name="energySettlementPanel")
     */
    public function indexAction(Request $request, EntityManager $em, SettlementModel $settlementModel, EasyAdminModel $easyAdminModel, Initializer $initializer)
    {
        $form = $this->createForm(SettlementType::class);
        $form->handleRequest($request);

        $records = [];
        $recordsAll = [];
        $data = [];
        $clientAndContract = null;
        if ($form->isSubmitted() && $form->isValid() || $request->query->get('pp')) {
            if ($form->isSubmitted()) {
                $pp = $form->get('pp')->getData();
                $dateFrom = $form->get('dateFrom')->getData();
                $dateTo = $form->get('dateTo')->getData();
                $omitCalculateDateFrom = $form->get('omitCalculateDateFrom')->getData() ? true : false;
            } else {
                $pp = $request->query->get('pp');
                $dateFrom = $request->query->get('dateFrom') ? \DateTime::createFromFormat('d-m-Y', $request->query->get('dateFrom'))->setTime(0, 0) : null;
                $dateTo = $request->query->get('dateTo') ? \DateTime::createFromFormat('d-m-Y', $request->query->get('dateTo'))->setTime(0, 0) : null;
                $omitCalculateDateFrom = $request->query->get('omitCalculateDateFrom') ? true : false;
            }

            $clientAndContract = $settlementModel->getClientWithContractByPp($pp);
            if (!$clientAndContract) {
                die('Nie znaleziono klienta z umową o podanym numerze PP.');
            }

            try {
                $data = $settlementModel->manageAndPrepareData($pp, true, $omitCalculateDateFrom, $dateFrom, $dateTo);
            } catch (\Exception $e) {
                die($e->getMessage());
            }
            $recordsAll = $data['recordsAll'];
            $records = $data['records'];

            // Marks records that are choosen for tests
            $this->markChosenRecords($recordsAll, $records);


            // generate invoice
            if ($form->getClickedButton() && $form->getClickedButton()->getName() == 'generate') {
                if (count($data['errors'])) {
                    $this->addFlash('error', 'Nie można wygenerować faktury - sprawdź komunikaty błędów.');
                    return $this->redirectToRoute('energySettlementPanel');
                }

                $invoiceType = $form->get('invoiceType')->getData();
                $config = $easyAdminModel->getEntityConfigByEntityName($invoiceType);

                /** @var ContractEnergyBase $contract */
                $contract = $clientAndContract->getContract();
                if (!$contract->getContractFromDate()) {
                    die('Na umowie nie ma daty obowiązywania umowy od');
                }

                /** @var PriceList $priceList */
                $priceList = $contract->getPriceListByDate($dateFrom);
                if (!$priceList) {
                    die('Na umowie nie ma cennika');
                }

                $createdDate = (new \DateTime())->setTime(0, 0);
                $settlementModel->generateInvoiceRecordFromData($data, $config, $contract->getType(), $createdDate, (clone $createdDate)->modify('+' . $priceList->getDateOfPaymentDays() . ' days'));

                $this->addFlash('success', 'Rekord faktury został utworzony.');
                return $this->redirectToRoute('energySettlementPanel');
            }
        }

        return $this->render('@WecodersEnergyBundle/default/settlement-panel.html.twig', [
            'form' => $form->createView(),
            'records' => $records,
            'recordsAll' => $recordsAll,
            'dateFrom' => $clientAndContract ? $settlementModel->getDateFromWhichToFetchRecords($clientAndContract->getClient()) : null,
            'data' => $data,
            'client' => $clientAndContract ? $clientAndContract->getClient() : null,
            'contract' => $clientAndContract ? $clientAndContract->getContract() : null,
        ]);
    }

    private function markChosenRecords(&$recordsAll, &$recordsChosen)
    {
        $result = [];
        foreach ($recordsAll as $record) {
            $record->chosen = false;
            $result[$record->getId()] = $record;
        }

        if ($recordsChosen) {
            foreach ($recordsChosen as $record) {
                if (array_key_exists($record->getId(), $result)) {
                    $result[$record->getId()]->chosen = true;
                }
            }
        }
    }


}
