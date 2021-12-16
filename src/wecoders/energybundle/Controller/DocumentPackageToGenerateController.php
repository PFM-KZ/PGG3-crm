<?php

namespace Wecoders\EnergyBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Wecoders\EnergyBundle\Entity\DocumentPackageToGenerate;
use Wecoders\EnergyBundle\Entity\DocumentPackageToGenerateRecord;
use Wecoders\EnergyBundle\Service\DocumentPackageToGenerateModel;
use Wecoders\EnergyBundle\Service\DocumentPackageToGenerateRecordModel;

class DocumentPackageToGenerateController extends Controller
{
    /**
     * @Route("/document-package-to-generate/{id}", name="documentPackageToGenerate")
     */
    public function documentPackageToGenerateAction(
        Request $request,
        EntityManagerInterface $em,
        DocumentPackageToGenerateModel $documentPackageToGenerateModel,
        $id
    )
    {
        /** @var DocumentPackageToGenerate $package */
        $package = $documentPackageToGenerateModel->getRecord($id);
        if (!$package) {
            throw new NotFoundHttpException();
        }

        $packageRecords = $package->getPackageRecords();


        // ACTIONS

        // ============
        if ($request->request->get('multiClick')) {
            $selectedRows = $request->get('selectedRows');

            if ($selectedRows) {
                $selectedPackageRecords = [];
                foreach ($selectedRows as $rowId) {
                    $row = $this->getDoctrine()->getRepository(DocumentPackageToGenerateRecordModel::ENTITY)->find($rowId);
                    if ($row) {
                        $selectedPackageRecords[] = $row;
                    }
                }

                if (count($selectedPackageRecords)) {
                    /** @var DocumentPackageToGenerateRecord $packageRecord */
                    foreach ($selectedPackageRecords as $packageRecord) {
                        if (
                            $packageRecord->getStatus() == DocumentPackageToGenerateRecordModel::STATUS_WAITING_TO_PROCESS ||
                            $packageRecord->getStatus() == DocumentPackageToGenerateRecordModel::STATUS_PROCESS_ERROR
                        ) {
                            $packageRecord->setStatus(DocumentPackageToGenerateRecordModel::STATUS_IN_PROCESS);
                            $packageRecord->setErrorMessage(null);
                            $em->persist($packageRecord);
                            $em->flush($packageRecord);
                        } elseif (
                            $packageRecord->getStatus() == DocumentPackageToGenerateRecordModel::STATUS_WAITING_TO_GENERATE ||
                            $packageRecord->getStatus() == DocumentPackageToGenerateRecordModel::STATUS_GENERATE_ERROR
                        ) {
                            $packageRecord->setStatus(DocumentPackageToGenerateRecordModel::STATUS_GENERATE);
                            $packageRecord->setErrorMessage(null);
                            $em->persist($packageRecord);
                            $em->flush($packageRecord);
                        }
                    }

                    $this->addFlash('success', 'Wykonano');
                    return $this->redirectToRoute('documentPackageToGenerate', ['id' => $id, 'entity' => $request->query->get('entity')]);
                }
            }
        }

        $changeStatusToProcess = $request->request->get('changeStatusToProcessAction');
        if ($changeStatusToProcess) {
            /** @var DocumentPackageToGenerateRecord $packageRecord */
            $packageRecord = $this->getDoctrine()->getRepository(DocumentPackageToGenerateRecordModel::ENTITY)->find($changeStatusToProcess);
            if ($packageRecord) {
                $packageRecord->setStatus(DocumentPackageToGenerateRecordModel::STATUS_IN_PROCESS);
                $packageRecord->setErrorMessage(null);
                $em->persist($packageRecord);
                $em->flush();
            }

            return $this->redirectToRoute('documentPackageToGenerate', ['id' => $id, 'entity' => $request->query->get('entity')]);
        }

        $changeStatusToProcess = $request->request->get('changeStatusToGenerateAction');
        if ($changeStatusToProcess) {
            /** @var DocumentPackageToGenerateRecord $packageRecord */
            $packageRecord = $this->getDoctrine()->getRepository(DocumentPackageToGenerateRecordModel::ENTITY)->find($changeStatusToProcess);
            if ($packageRecord) {
                $packageRecord->setStatus(DocumentPackageToGenerateRecordModel::STATUS_GENERATE);
                $packageRecord->setErrorMessage(null);
                $em->persist($packageRecord);
                $em->flush();
            }

            return $this->redirectToRoute('documentPackageToGenerate', ['id' => $id, 'entity' => $request->query->get('entity')]);
        }

        return $this->render('@WecodersEnergyBundle/default/document-package.html.twig', [
            'package' => $package,
            'records' => $packageRecords,
        ]);
    }

}
