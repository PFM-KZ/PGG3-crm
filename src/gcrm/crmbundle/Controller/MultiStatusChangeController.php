<?php

namespace GCRM\CRMBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use GCRM\CRMBundle\Entity\ContractEnergyBase;
use GCRM\CRMBundle\Entity\StatusDepartment;
use GCRM\CRMBundle\Form\MultiStatusChangeType;
use GCRM\CRMBundle\Service\ContractModel;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Wecoders\EnergyBundle\Service\ContractAccessor;
use Wecoders\EnergyBundle\Service\SpreadsheetReader;

class MultiStatusChangeController extends Controller
{
    /**
     * @Route("/multi-status-change", name="multiStatusChange")
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
        $form = $this->createForm(MultiStatusChangeType::class);
        $form->handleRequest($request);

        // INPUT FILE RECORDS
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            /** @var StatusDepartment $statusDepartment */
            $statusDepartment = $form->get('statusDepartment')->getData();
            $statusContract = $form->get('statusContract')->getData();

            $kernelRootDir = $container->get('kernel')->getRootDir();
            $tmpFilename = 'tmp-mutli-status-change';
            $absoluteUploadDirectoryPath = $kernelRootDir . '/../var/data';
            $fullPathToFile = $kernelRootDir . '/../var/data/' . $tmpFilename;
            if (file_exists($fullPathToFile)) {
                unlink($fullPathToFile);
            }

            $file->move($absoluteUploadDirectoryPath, $tmpFilename);

            if (file_exists($fullPathToFile)) {
                $rows = $spreadsheetReader->fetchRows('Xlsx', $fullPathToFile, 2, 'A');

                // validate
                $notFound = [];
                $errors = [];
                foreach ($rows as $row) {
                    $value = $row[0];
                    /** @var ContractEnergyBase $contract */
                    $contract = $contractAccessor->accessContractBy('number', $value, 'accountNumberIdentifier');
                    if (!$contract) {
                        $notFound[] = $value;
                    }

                    // check if contract is in chosen department
                    /** @var StatusDepartment $contractDepartment */
                    $contractDepartment = $contract->getStatusDepartment();
                    if (!$contractDepartment) {
                        $errors[] = 'Umowa nie ma przypisanego departamentu: ' . $contract->getContractNumber();
                    }

                    if ($contractDepartment->getId() != $statusDepartment->getId()) {
                        $errors[] = 'Umowa jest w innym departamencie niż wybrany: ' . $contract->getContractNumber();
                    }
                }
                if (count($notFound)) {
                    return new Response('Plik nie został wgrany ponieważ nie znaleziono klientów/umów dla danych id klientów (popraw dane z pliku i wgraj jeszcze raz):<br>' . implode('<br>', $notFound));
                }
                if (count($errors)) {
                    return new Response('Plik nie został wgrany ponieważ wystąpiły błędy podczas walidacji danych (popraw dane z pliku i wgraj jeszcze raz):<br>' . implode('<br>', $errors));
                }

                // process
                $em->getConnection()->beginTransaction();
                $updated = false;
                $contractsBeforeChangesByTypeAndIds = [];
                try {
                    $method = null;
                    if ($statusDepartment->getCode() == 'authorization') {
                        $method = 'setStatusContractAuthorization';
                    } elseif ($statusDepartment->getCode() == 'verification') {
                        $method = 'setStatusContractVerification';
                    } elseif ($statusDepartment->getCode() == 'administration') {
                        $method = 'setStatusContractAdministration';
                    } elseif ($statusDepartment->getCode() == 'process') {
                        $method = 'setStatusContractProcess';
                    } elseif ($statusDepartment->getCode() == 'finances') {
                        $method = 'setStatusContractFinances';
                    }

                    if (!$method) {
                        throw new \RuntimeException();
                    }

                    foreach ($rows as $row) {
                        $value = $row[0];
                        $contract = $contractAccessor->accessContractBy('number', $value, 'accountNumberIdentifier');

                        // clone to further use (fetch status before changes)
                        if (!array_key_exists($contract->getType(), $contractsBeforeChangesByTypeAndIds)) {
                            $contractsBeforeChangesByTypeAndIds[$contract->getType()] = [];
                        }
                        $contractsBeforeChangesByTypeAndIds[$contract->getType()][$contract->getId()] = clone $contract;

                        // set new status
                        $contract->$method($statusContract);

                        $em->persist($contract);
                        $em->flush();
                    }



                    $em->getConnection()->commit();
                    $updated = true;
                    $this->addFlash('success', 'Statusy zostały zmienione.');
                } catch (\Exception $e) {
                    $em->getConnection()->rollBack();
                    $this->addFlash('error', 'Statusy nie zostały zmienione.');
                }

                if ($updated) {
                    $em->getConnection()->beginTransaction();
                    try {
                        foreach ($rows as $row) {
                            $value = $row[0];
                            $contract = $contractAccessor->accessContractBy('number', $value, 'accountNumberIdentifier');
                            $contractModel->onPostUpdate($contract);
                            $contractModel->addLog($contractsBeforeChangesByTypeAndIds[$contract->getType()][$contract->getId()], $contract);
                        }

                        $em->getConnection()->commit();
                        $this->addFlash('success', 'Akcje statusów zostały wykonane.');
                    } catch (\Exception $e) {
                        $em->getConnection()->rollBack();
                        $this->addFlash('error', 'Akcje statusów nie zostały wykonane.');
                    }
                }

                // clear tmp file
                unlink($fullPathToFile);

                return $this->redirectToRoute('multiStatusChange');
            }
        }

        return $this->render('@WecodersEnergyBundle/default/multi-status-change.html.twig', [
            'formInputFile' => $form->createView(),
        ]);
    }

}
