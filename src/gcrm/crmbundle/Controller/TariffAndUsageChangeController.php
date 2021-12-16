<?php

namespace GCRM\CRMBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use GCRM\CRMBundle\Entity\ContractEnergyAndPriceList;
use GCRM\CRMBundle\Entity\ContractEnergyBase;
use GCRM\CRMBundle\Entity\ContractGasAndPriceList;
use GCRM\CRMBundle\Form\TariffAndUsageChangeType;
use GCRM\CRMBundle\Service\ContractModel;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Wecoders\EnergyBundle\Entity\PriceList;
use Wecoders\EnergyBundle\Service\ContractAccessor;
use Wecoders\EnergyBundle\Service\SpreadsheetReader;

class TariffAndUsageChangeController extends Controller
{
    /**
     * @Route("/tariff-and-usage-change", name="tariffAndUsageChange")
     */
    public function multiStatusChangeAction(
        Request $request,
        EntityManagerInterface $em,
        ContainerInterface $container,
        SpreadsheetReader $spreadsheetReader,
        ContractAccessor $contractAccessor,
        ContractModel $contractModel
    )
    {
        $em = $this->getDoctrine()->getManager();
        $form = $this->createForm(TariffAndUsageChangeType::class);
        $form->handleRequest($request);

        // INPUT FILE RECORDS
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();

            $kernelRootDir = $container->get('kernel')->getRootDir();
            $tmpFilename = 'tmp-tariff-and-usage-change';
            $absoluteUploadDirectoryPath = $kernelRootDir . '/../var/data';
            $fullPathToFile = $kernelRootDir . '/../var/data/' . $tmpFilename;
            if (file_exists($fullPathToFile)) {
                unlink($fullPathToFile);
            }

            $file->move($absoluteUploadDirectoryPath, $tmpFilename);

            if (file_exists($fullPathToFile)) {
                $rows = $spreadsheetReader->fetchRows('Xlsx', $fullPathToFile, 2, 'D');
                // validate
                $items = [];
                $notFound = [];
                $errors = [];
                foreach ($rows as $row) {
                    $clientId = $row[0];

                    /** @var ContractEnergyBase $contract */
                    $contract = $contractAccessor->accessContractBy('id', $clientId, 'client');
                    if ($contract) {
                        $items[] = $clientId;
                    }



                }
                if (count($items) == 0) {
                    return new Response('Plik nie został wgrany ponieważ nie znaleziono klientów/umów dla danych id klientów (popraw dane z pliku i wgraj jeszcze raz):<br>' . implode('<br>', $notFound));
                }
                if (count($errors)) {
                    return new Response('Plik nie został wgrany ponieważ wystąpiły błędy podczas walidacji danych (popraw dane z pliku i wgraj jeszcze raz):<br>' . implode('<br>', $errors));
                }

                $priceListRepo = $em->getRepository('WecodersEnergyBundle:PriceList');
//                $contractGasAndPriceListRepo = $em->getRepository('GCRMCRMBundle:ContractGasAndPriceList');
//                $contractEnergyAndPriceListRepo = $em->getRepository('GCRMCRMBundle:ContractEnergyAndPriceList');

                // process
                $em->getConnection()->beginTransaction();
                $index = 0;
                try {
                    foreach ($rows as $row) {
                        if ($row[0] == "") { continue; }

                        $clientId = $row[0];
                        $fromDateRaw = $row[1];
                        $newPriceListId = $row[2];
                        $newConsumption = $row[3];

                        $newPriceList = $priceListRepo->findOneBy([
                            'id' => $newPriceListId
                        ]);

                        /** @var ContractEnergyBase $contract */
                        $contract = $contractAccessor->accessContractBy('id', $clientId, 'client');
                        $fromDate = \DateTime::createFromFormat('Y-m-d', $fromDateRaw);

                        if ($fromDate){
                            $contractsGaz = $em->getRepository('GCRMCRMBundle:ContractGasAndPriceList')->findBy(['contract' => $contract]);
                            $contractsEnergy = $em->getRepository('GCRMCRMBundle:ContractEnergyAndPriceList')->findBy(['contract' => $contract]);
                            if ($contractsGaz) { $this->removeNewestPriceLists($contractsGaz, $fromDateRaw, $em); }

                            if ($contractsEnergy) { $this->removeNewestPriceLists($contractsEnergy, $fromDateRaw, $em); }


                            $fromDate->setTime(0,0);
                        } else if (!$fromDate && $newPriceListId) {
                            return new Response('Błędny format dat YYYY-MM-DD lub brak.');
                        }

                        if ($contract->getType() == 'GAS' || $contract->getType() == 'gas'){
                            if (isset($newPriceListId) && !empty($newPriceListId)){
                                //Add new price list
                                $contractAndPricelist = new ContractGasAndPriceList();
                                $contractAndPricelist->setContract($contract);
                                $contractAndPricelist->setPriceList($newPriceList);
                                $contractAndPricelist->setFromDate($fromDate);
                            }
                        } elseif ($contract->getType() == 'ENERGY' || $contract->getType() == 'energy') {
                            if (isset($newPriceListId) && !empty($newPriceListId)){
                                //Add new price list

                                $contractAndPricelist = new ContractEnergyAndPriceList();
                                $contractAndPricelist->setContract($contract);
                                $contractAndPricelist->setPriceList($newPriceList);
                                $contractAndPricelist->setFromDate($fromDate);
                            }
                        } else {
                            die('Błędny typ umowy.');
                        }

                        //Update consumption
                        if(isset($newConsumption) && !empty($newConsumption)){
                            $contract->setConsumption($newConsumption);
                        }

                        $em->persist($contract);
                        if (isset($newPriceListId) && !empty($newPriceListId)){

                            $em->persist($contractAndPricelist);
                        }

                        $em->flush();
                        $index++;
                    }

                    $em->getConnection()->commit();
                    $this->addFlash('success', 'Sukces - zmieniono ' . $index . ' cenników.');
                } catch (\Exception $e) {
                    $em->getConnection()->rollBack();
                    $this->addFlash('error', 'Błąd! Cenniki nie zostały zmienione. ' . $e);
                }

                // clear tmp file
                unlink($fullPathToFile);

                return $this->redirectToRoute('tariffAndUsageChange');
            }
        }

        return $this->render('@WecodersEnergyBundle/default/tariff-and-usage-change.html.twig', [
            'formInputFile' => $form->createView(),
        ]);
    }

    private function removeNewestPriceLists($contracts, $priceListDateFrom, $em)
    {
        /** @var ContractGasAndPriceList $contract */
        foreach ($contracts as $contract)
        {
            if ($priceListDateFrom != null && $contract->getFromDate() != null && $contract->getFromDate()->format('Y-m-d') >= $priceListDateFrom)
            {
                $em->remove($contract);
            }
        }

    }


}
