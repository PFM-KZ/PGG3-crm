<?php

namespace Wecoders\EnergyBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\ClientAndContractEnergy;
use GCRM\CRMBundle\Entity\ClientAndContractGas;
use GCRM\CRMBundle\Entity\ContractEnergy;
use GCRM\CRMBundle\Entity\ContractEnergyAndPriceList;
use GCRM\CRMBundle\Entity\ContractGas;
use GCRM\CRMBundle\Entity\ContractGasAndPriceList;
use GCRM\CRMBundle\Service\BillingDocument\Initializer;
use GCRM\CRMBundle\Service\ClientModel;
use GCRM\CRMBundle\Service\ContractModel;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wecoders\EnergyBundle\Entity\DebitNote;
use Wecoders\EnergyBundle\Entity\PriceList;
use Wecoders\EnergyBundle\Service\EnergyTypeModel;

class UpdateClientGasContractPricelistCommand extends Command
{
    /* @var EntityManager */
    private $em;

    private $container;

    private $contractModel;

    private $clientModel;

    private $initializer;

    public function __construct(EntityManager $em, ContainerInterface $container, ContractModel $contractModel, ClientModel $clientModel, Initializer $initializer)
    {
        $this->em = $em;
        $this->container = $container;
        $this->contractModel = $contractModel;
        $this->clientModel = $clientModel;
        $this->initializer = $initializer;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('wecodersenergybundle:update-client-gas-contract-pricelist-command')
            ->addArgument('filename', InputArgument::REQUIRED, 'Filename with extension without path. The path is defined: /../var/data/')
            ->addArgument('pricelist', InputArgument::REQUIRED, 'Pricelist ID to append')
            ->addArgument('fromDate', InputArgument::REQUIRED, 'Datetime representation in format "d.m.y" of the Pricelist "fromDate property"')
            ->addArgument('deleteFurtherPriceLists', InputArgument::OPTIONAL, '0/1 - delete further price lists')
            ->setDescription('Update client gas contract pricelist.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        $this->em->getConnection()->beginTransaction();

        try {
            /** @var Xlsx $reader */
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
            $rows = $this->getDataRowsUniversal($reader, $this->container->get('kernel')->getRootDir() . '/../var/data/' . $input->getArgument('filename'), 2, 'Q');
            if (!$rows) {
                dump('no data or bad filename specified');
                die;
            }

            /** @var PriceList $pricelist */
            $pricelist = $this->em->getRepository(PriceList::class)->find($input->getArgument('pricelist'));
            $deleteFurther = $input->getArgument('deleteFurtherPriceLists');
            $fromDate = \DateTime::createFromFormat('d.m.Y', $input->getArgument('fromDate'));
            if (!$pricelist) {
                dump('Invalid pricelist. Exiting the commannd');
                die;
            }

            foreach ($rows as $row) {
                /** @var Client $client */
                $client = $this->clientModel->getClientByBadgeId($row[0]);
                if (!$client) {
                    dump('Client with badgeId "' . $row[0] . '" was not found.');
                    continue;
                }

                if ($deleteFurther) {
                    if ($pricelist->getEnergyType() == EnergyTypeModel::TYPE_GAS) {
                        /** @var ClientAndContractGas $clientAndGasContract */
                        foreach($client->getClientAndGasContracts() as $clientAndGasContract) {
                            /** @var ContractGas $contract */
                            $contract = $clientAndGasContract->getContract();
                            if (!$contract) {
                                dump('Invalid client and gas contract with ID "'. $clientAndGasContract->getId() . '"');
                                continue;
                            }

                            $contractAndPriceLists = $contract->getContractAndPriceLists();
                            /** @var ContractEnergyAndPriceList $contractAndPriceList */
                            foreach ($contractAndPriceLists as $contractAndPriceList) {
                                if ($fromDate <= $contractAndPriceList->getFromDate()) {
                                    $this->em->remove($contractAndPriceList);
                                    $this->em->flush($contractAndPriceList);
                                }
                            }
                        }
                    }

                    if ($pricelist->getEnergyType() == EnergyTypeModel::TYPE_ENERGY) {
                        /** @var ClientAndContractEnergy $clientAndContract */
                        foreach($client->getClientAndEnergyContracts() as $clientAndContract) {
                            /** @var ContractEnergy $contract */
                            $contract = $clientAndContract->getContract();
                            if (!$contract) {
                                dump('Invalid client and gas contract with ID "'. $clientAndContract->getId() . '"');
                                continue;
                            }

                            $contractAndPriceLists = $contract->getContractAndPriceLists();
                            /** @var ContractEnergyAndPriceList $contractAndPriceList */
                            foreach ($contractAndPriceLists as $contractAndPriceList) {
                                if ($fromDate <= $contractAndPriceList->getFromDate()) {
                                    $this->em->remove($contractAndPriceList);
                                    $this->em->flush($contractAndPriceList);
                                }
                            }
                        }
                    }
                }

                if ($pricelist->getEnergyType() == EnergyTypeModel::TYPE_GAS) {
                    /** @var ClientAndContractGas $clientAndGasContract */
                    foreach($client->getClientAndGasContracts() as $clientAndGasContract) {
                        $contract = $clientAndGasContract->getContract();

                        if (!$contract) {
                            dump('Invalid client and gas contract with ID "'. $clientAndGasContract->getId() . '"');
                            continue;
                        }

                        $contractAndPricelist = new ContractGasAndPriceList();
                        $contractAndPricelist->setContract($contract);
                        $contractAndPricelist->setPriceList($pricelist);
                        $contractAndPricelist->setFromDate($fromDate);
                        $this->em->persist($contractAndPricelist);
                        $this->em->flush($contractAndPricelist);
                    }
                }

                if ($pricelist->getEnergyType() == EnergyTypeModel::TYPE_ENERGY) {
                    /** @var ClientAndContractEnergy $clientAndContract */
                    foreach($client->getClientAndEnergyContracts() as $clientAndContract) {
                        $contract = $clientAndContract->getContract();

                        if (!$contract) {
                            dump('Invalid client and gas contract with ID "'. $clientAndContract->getId() . '"');
                            continue;
                        }

                        $contractAndPricelist = new ContractEnergyAndPriceList();
                        $contractAndPricelist->setContract($contract);
                        $contractAndPricelist->setPriceList($pricelist);
                        $contractAndPricelist->setFromDate($fromDate);
                        $this->em->persist($contractAndPricelist);
                        $this->em->flush($contractAndPricelist);
                    }
                }

                dump($row[0]);
            }
            $this->em->getConnection()->commit();
            dump('Success');
        } catch(\Exception $exception) {
            dump($exception->getMessage());
            dump('An exception occurred: ' . $exception->getMessage());
            dump('Rolling back the database');
            $this->em->getConnection()->rollBack();
        }
    }

    protected function getDataRowsUniversal($reader, $file, $firstDataRowIndex, $highestColumn)
    {
        $spreadsheet = $reader->load($file);
        $worksheet = $spreadsheet->getActiveSheet();

        $highestRow = $worksheet->getHighestRow(); // e.g. 10
        $highestColumn++;

        $rows = [];

        for ($row = $firstDataRowIndex; $row <= $highestRow; ++$row) {
            $rows[$row] = [];
            for ($col = 'A'; $col != $highestColumn; ++$col) {
                $rows[$row][] = $worksheet->getCell($col . $row)->getFormattedValue();
            }
        }

        return $rows;
    }

}