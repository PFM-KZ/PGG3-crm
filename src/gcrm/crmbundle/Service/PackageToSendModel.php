<?php

namespace GCRM\CRMBundle\Service;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\PackageToSend;
use GCRM\CRMBundle\Entity\User;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PackageToSendModel
{
    private $em;
    private $container;

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function generateNumber($branchTypeCode, $contractType)
    {
        $date = new \DateTime();
        $id = $this->getNextIdNumberOfAddedRecord();

        $number = $branchTypeCode . '/' . $contractType . '/' . $id . '/' . $date->format('d/m/Y');

        return $number;
    }

    public function changeNumberToDisplayFormat($number)
    {
        return str_replace('_', '//', $number);
    }

    public function getLastAddedRecord()
    {
        return $this->em->getRepository('GCRMCRMBundle:PackageToSend')->findOneBy([], ['id' => 'DESC']);
    }

    private function getNextIdNumberOfAddedRecord()
    {
        $id = 1;
        /** @var PackageToSend $lastAddedRecord */
        $lastAddedRecord = $this->getLastAddedRecord();
        if ($lastAddedRecord) {
            $id = $lastAddedRecord->getId();
            $id++;
        }

        return $id;
    }

    public function generateDocument(PackageToSend $packageToSend, User $currentUser)
    {
        $dataProvider = [
            'entity' => null,
            'entityClientAndContract' => null,
        ];
        if ($packageToSend->getContractType() == 'gas') {
            $dataProvider['entity'] = 'GCRMCRMBundle:ContractGas';
            $dataProvider['entityClientAndContract'] = 'GCRMCRMBundle:ClientAndContractGas';
        } elseif ($packageToSend->getContractType() == 'energy') {
            $dataProvider['entity'] = 'GCRMCRMBundle:ContractEnergy';
            $dataProvider['entityClientAndContract'] = 'GCRMCRMBundle:ClientAndContractEnergy';
        }

        if (!$dataProvider['entity']) {
            throw new NotFoundHttpException();
        }

        $contracts = $this->getContractsFromPackage($packageToSend, $dataProvider['entity']);

        // need to download clients data as well like name, surname so there need to be downloaded parent entity
        // that holds client and contract data
        $clientsAndContracts = $this->em->getRepository($dataProvider['entityClientAndContract'])->findBy([
            'contract' => $contracts
        ]);
        if (!$clientsAndContracts) {
            return null;
        }


        $rows = [];
        foreach ($clientsAndContracts as $clientsAndContract) {
            $client = $clientsAndContract->getClient();
            $contract = $clientsAndContract->getContract();

            $rows[] = [
                'client' => $client,
                'contract' => $contract
            ];
        }

        $kernelRootDir = $this->container->get('kernel')->getRootDir();

        $packagesDir = $kernelRootDir . '/../var/data/uploads/packages';
        if (!file_exists($packagesDir)) {
            mkdir($packagesDir, 0777);
        }

        $packageDir = $packagesDir . '/' . $packageToSend->getId();
        if (!file_exists($packageDir)) {
            mkdir($packageDir, 0777);
        }

        $template = new \PhpOffice\PhpWord\TemplateProcessor($packagesDir . '/package-to-send-template.docx');

        $headings = $this->tablePackageHeadings();
        $rows = $this->tablePackageRows($rows);
        $this->createTable($template, 'tableRecords', 10150, $headings, $rows, 'center');

        $template->setValue('packageNumber', $packageToSend->getNumber());
        $now = new \DateTime();
        $template->setValue('generatedDate', $now->format('d-m-Y H:i:s'));
        $template->setValue('packagedDate', $packageToSend->getCreatedAt()->format('d-m-Y H:i:s'));
        $template->setValue('generatedBy', '#' . $currentUser->getId() . ' ' . $currentUser->getName() . ' ' . $currentUser->getSurname());
        $addedBy = $packageToSend->getAddedBy();
        $template->setValue('packagedBy', '#' . $addedBy->getId() . ' ' . $addedBy->getName() . ' ' . $addedBy->getSurname());
        $template->setValue('fromBranch', $packageToSend->getFromBranch());
        $template->setValue('toBranch', $packageToSend->getToBranch());

        $template->saveAs($packageDir . '/' . 'label.docx');
        shell_exec('unoconv -f pdf ' . $packageDir . '/' . 'label.docx');

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename=' . $packageToSend->getNumber() . '.pdf');

        echo readfile($packageDir . '/label.pdf');
        exit;
    }

    private function tablePackageHeadings()
    {
        return [
            [
                'text' => 'Lp.',
                'width' => 450,
            ],
            [
                'text' => 'Numer umowy',
            ],
            [
                'text' => 'Imię',
            ],
            [
                'text' => 'Nazwisko',
            ],
            [
                'text' => 'Marka',
            ],
        ];
    }

    private function tablePackageRows($records)
    {
        $rows = [];
        $lp = 1;

        foreach ($records as $record) {
            $this->addTableDetailsRow($rows, $lp, $record);
            $lp++;
        }

        if (!count($rows)) {
            return [];
        }

        return $rows;
    }

    private function addTableDetailsRow(&$rows, $lp, $record)
    {
        $rows[] = [
            [
                'text' => $lp,
                'fontStyle' => [
                    'append' => [
                        'bold' => true,
                    ]
                ],
            ],
            [
                'text' => $record['contract']->getContractNumber(),
            ],
            [
                'text' => $record['client']->getName(),
            ],
            [
                'text' => $record['client']->getSurname(),
            ],
            [
                'text' => $record['contract']->getBrand(),
            ],
        ];
    }

    private function createTable(TemplateProcessor &$template, $variableName, $boxSize, $headings, $rows, $pStyleAlign = 'left')
    {
        $cellsCount = count($headings) ? count($headings) : count($rows);
        foreach ($headings as $cell) {
            if (isset($cell['width'])) {
                $cellsCount--;
                $boxSize = $boxSize - $cell['width'];
            }
        }
        $cellCalculatedSize = $boxSize / $cellsCount;



        $paramsTable = array(
            'tableAlign' => 'center',
        );

        $cellStyle = [
            'valign' => 'center',
            'borderBottomColor' =>'black',
            'borderBottomSize' => 1,
        ];

        $headingsStyle = [
            'name' => 'Carlito',
            'size' => '9',
            'bold' => true,
        ];

        $fontStyle = [
            'name' => 'Carlito',
            'size' => '8'
        ];
        $pStyle = [
            'align' => $pStyleAlign,
            'spaceBefore' => 0,
            'spaceAfter' => 0,
            'lineHeight' => 1,
        ];

        $table = new Table($paramsTable);
        $table->addRow();

        foreach ($headings as $cell) {
            $cellSize = isset($cell['width']) ? $cell['width'] : $cellCalculatedSize;

            $tmpCellStyle = $cellStyle;
            $tmpCellStyle = isset($cell['cellStyle']) ? (
            isset($cell['cellStyle']['append']) ? array_merge($cellStyle, $cell['cellStyle']['append']) : $cell['cellStyle']['new']
            ) : $tmpCellStyle;

            $tmpPstyle = $pStyle;
            $tmpPstyle = isset($cell['pStyle']) ? (
            isset($cell['pStyle']['append']) ? array_merge($pStyle, $cell['pStyle']['append']) : $cell['pStyle']['new']
            ) : $tmpPstyle;

            $tmpFontStyle = $headingsStyle;
            $tmpFontStyle = isset($cell['fontStyle']) ? (
            isset($cell['fontStyle']['append']) ? array_merge($headingsStyle, $cell['fontStyle']['append']) : $cell['fontStyle']['new']
            ) : $tmpFontStyle;

            $table->addCell($cellSize, $tmpCellStyle)->addText($cell['text'], $tmpFontStyle, $tmpPstyle);
        }

        foreach ($rows as $row) {
            $table->addRow();
            $index = 0;
            foreach ($row as $cell) {
                $tmpCellStyle = $cellStyle;
                $tmpCellStyle = isset($cell['cellStyle']) ? (
                isset($cell['cellStyle']['append']) ? array_merge($cellStyle, $cell['cellStyle']['append']) : $cell['cellStyle']['new']
                ) : $tmpCellStyle;

                $tmpPstyle = $pStyle;
                $tmpPstyle = isset($cell['pStyle']) ? (
                isset($cell['pStyle']['append']) ? array_merge($pStyle, $cell['pStyle']['append']) : $cell['pStyle']['new']
                ) : $tmpPstyle;

                $tmpFontStyle = $fontStyle;
                $tmpFontStyle = isset($cell['fontStyle']) ? (
                isset($cell['fontStyle']['append']) ? array_merge($fontStyle, $cell['fontStyle']['append']) : $cell['fontStyle']['new']
                ) : $tmpFontStyle;

                $table->addCell(null, $tmpCellStyle)->addText(iconv('UTF-8', 'UTF-8', $cell['text']), $tmpFontStyle, $tmpPstyle);
                $index++;
            }
        }
        $template->setComplexBlock($variableName, $table);
    }

    private function getContractsFromPackage(PackageToSend $packageToSend, $entity)
    {
        $qb = $this->em->createQueryBuilder();
        $q = $qb->select(['a'])
            ->from($entity, 'a')
            ->where('a.id IN (:ids)')
            ->setParameters([
                'ids' => explode(',', $packageToSend->getContractIds())
            ])
            ->getQuery()
        ;

        return $q->getResult();
    }
}