<?php

namespace Wecoders\EnergyBundle\Controller;

use Doctrine\ORM\EntityManager;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Wecoders\EnergyBundle\Form\ImporterType;
use Wecoders\EnergyBundle\Service\Importer;
use Wecoders\EnergyBundle\Service\ImporterStrategies\ImporterStrategyInterface;
use Wecoders\EnergyBundle\Service\OsdModel;

class InputController extends Controller
{
    /**
     * @Route("/energy-data-panel", name="energyDataPanel")
     */
    public function indexAction(Request $request, EntityManager $em, ContainerInterface $container, Importer $importer)
    {
        $optionArray = $importer->getOptionArray();

        $form = $this->createForm(ImporterType::class, null, ['data' => $optionArray]);
        $form->handleRequest($request);

        $rows = null;
        if ($form->isSubmitted() && $form->isValid()) {
            $kernelRootDir = $this->get('kernel')->getRootDir();

            $files = $form->get('files')->getData();
            $operator = $form->get('operator')->getData();

            // gets strategy
            /** @var ImporterStrategyInterface $strategy */
            $strategy = $importer->getStrategyByCode($operator);
            if (!$strategy) {
                die('This importing option does not exist. Contact with administrator.');
            }

            /** @var UploadedFile $file */
            foreach ($files as $file) {
                $absoluteUploadDirectoryPath = $strategy->getAbsoluteUploadDirectoryPath($kernelRootDir);
                $fileOriginalName = $file->getClientOriginalName();
                $tmpFilename = 'tmp';
                $fullPathToFile = $absoluteUploadDirectoryPath . '/' . $fileOriginalName;
                $fullPathToTmpFile = $absoluteUploadDirectoryPath . '/' . $tmpFilename;
                if (file_exists($fullPathToTmpFile)) {
                    unlink($fullPathToTmpFile);
                }

                // before move validate if file already exist
                if (file_exists($fullPathToFile)) {
                    $this->addFlash('notice', 'Plik o nazwie: ' . $fileOriginalName . ' nie został wgrany ponieważ znajduje się już w systemie.');
                    continue;
                }
                $file->move($absoluteUploadDirectoryPath, $tmpFilename);

                $rows = $strategy->load($fullPathToTmpFile, $fileOriginalName);
                try {
                    $objects = $strategy->hydrate($rows);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Plik o nazwie: ' . $fileOriginalName . ' nie został wgrany ponieważ wystąpił błąd podczas jego przetwarzania.' . ' ' . $e->getMessage());
                    continue;
                }
                $strategy->save($objects);
                rename($fullPathToTmpFile, $fullPathToFile);
            }

            $this->addFlash('success', 'Przetworzono');
            return $this->redirectToRoute('energyDataPanel');
        }



        // GET NUMBERS
        $conn = $em->getConnection();

        $sql = '
SELECT 
  a.code, COUNT(DISTINCT(a.filename)) as number
FROM `energy_data` a
GROUP BY a.code
';

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();

        $codeMap = OsdModel::getOptionArray();
        $newResult = [];

        foreach ($codeMap as $osdCode => $osdTitle) {
            $newResult[$osdCode] = [
                'title' => $osdTitle,
                'number' => 0,
            ];

            if (!$result) {
                continue;
            }

            foreach ($result as $dbData) {
                if ($dbData['code'] == $osdCode) {
                    $newResult[$osdCode]['number'] = $dbData['number'];
                    break;
                }
            }
        }

        return $this->render('@WecodersEnergyBundle/default/index.html.twig', [
            'form' => $form->createView(),
            'recordsAdded' => $rows,
            'filesData' => $newResult,
        ]);
    }

    /**
     * @Route("/energy-data-panel/download-files/{code}", name="energyDataPanelDownloadUniqueFilesByCode")
     */
    public function downloadUniqueFilesByCodeAction(EntityManager $em, $code)
    {
        // for secure
        if (!is_numeric($code)) {
            throw new NotFoundHttpException();
        }
        $code = (int) $code;

        $conn = $em->getConnection();
        $sql = '
SELECT 
  DISTINCT(a.filename)
FROM `energy_data` a
WHERE a.code = ' . $code;

        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();

        if (!$result) {
            die('Brak danych.');
        }

        $spreadsheet = new Spreadsheet();

        $spreadsheet->getActiveSheet()->setCellValue('A1', 'Lp.');
        $spreadsheet->getActiveSheet()->setCellValue('B1', 'Kod');
        $spreadsheet->getActiveSheet()->setCellValue('C1', 'Nazwa pliku');

        $index = 2;
        foreach ($result as $item) {
            $spreadsheet->getActiveSheet()->setCellValue('A' . $index, $index - 1);
            $spreadsheet->getActiveSheet()->setCellValue('B' . $index, $code);
            $spreadsheet->getActiveSheet()->setCellValue('C' . $index, $item['filename']);

            $index++;
        }

        $this->downloadSpreadsheetAsXlsx($spreadsheet);
    }

    private function downloadSpreadsheetAsXlsx($spreadsheet)
    {
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="dane.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }
}
