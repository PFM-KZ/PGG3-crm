<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\ContractEnergyBase;
use GCRM\CRMBundle\Entity\Distributor;
use GCRM\CRMBundle\Entity\DistributorBranch;
use GCRM\CRMBundle\Entity\Seller;
use GCRM\CRMBundle\Service\SellerModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateInsertedDistributorsSellersCommand extends Command
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('appbundle:update-inserted-distributors-sellers');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $contractsGas = $this->em->getRepository('GCRMCRMBundle:ContractGas')->findAll();
        $contractsEnergy = $this->em->getRepository('GCRMCRMBundle:ContractEnergy')->findAll();

        $contracts = array_merge($contractsGas, $contractsEnergy);
        $this->update($contracts);

        dump('Success');
    }

    private function update($contracts)
    {
        $matchedDistributors = [];
        $matchedBranches = [];
        $matchedCurrentSellers = [];

        $unmatchedDistributors = [];
        $unmatchedBranches = [];
        $unmatchedCurrentSellers = [];

        $index = 1;

        $dbDistributors = $this->em->getRepository('GCRMCRMBundle:Distributor')->findAll();
        $distributorTauron = $this->em->getRepository('GCRMCRMBundle:Distributor')->findOneBy(['title' => 'Tauron Dystrybucja S.A.']);
        $dbDistributorBranches = $this->em->getRepository('GCRMCRMBundle:DistributorBranch')->findBy(['distributor' => $distributorTauron]);
        $dbSellers = $this->em->getRepository('GCRMCRMBundle:Seller')->findAll();
//
        /** @var ContractEnergyBase $contract */
        foreach ($contracts as $contract) {
            $distributorTitle = $contract->getDistributor();
            $distributorBranchTitle = $contract->getDistributorBranch();
            $sellerTitle = $contract->getCurrentSeller();

            // distributors
            if ($distributorTitle && $contract->getType() == 'ENERGY') {
                $found = false;
                /** @var Distributor $dbDistributor */
                foreach ($dbDistributors as $dbDistributor) {
                    $distributorTitle = mb_strtolower($distributorTitle);
                    $dbTitle = mb_strtolower($dbDistributor->getTitle());

                    if ($distributorTitle == $dbTitle) {
                        $found = $dbDistributor;
                        break;
                    }

                    if (strpos($dbTitle, $distributorTitle) !== false) {
                        $found = $dbDistributor;
                        break;
                    }

                    $distributorTitleFirstPart = explode(' ', $distributorTitle)[0];
                    if (strpos($dbTitle, $distributorTitleFirstPart) !== false) {
                        $found = $dbDistributor;
                        break;
                    }

                    $distributorTitleFirstPart = explode('-', $distributorTitle)[0];
                    if (strpos($dbTitle, $distributorTitleFirstPart) !== false) {
                        $found = $dbDistributor;
                        break;
                    }
                }

                if (!$found) {
                    $unmatchedDistributors[] = $distributorTitle;
                } else {
                    $matchedDistributors[] = $distributorTitle;
                    $contract->setDistributorObject($found);
                    $this->em->persist($contract);
                    $this->em->flush($contract);
                }
            }

            // distributor branches
            if ($distributorBranchTitle && $contract->getType() == 'ENERGY' && $contract->getDistributorObject()) {
                $found = false;

                /** @var Distributor $distributorObject */
                $distributorObject = $contract->getDistributorObject();
                if ($distributorObject->getTitle() == 'Tauron Dystrybucja S.A.') {

                    /** @var DistributorBranch $dbDistributorBranch */
                    foreach ($dbDistributorBranches as $dbDistributorBranch) {
                        $distributorBranchTitle = mb_strtolower($distributorBranchTitle);
                        $dbTitle = mb_strtolower($dbDistributorBranch->getTitle());

                        if ($distributorBranchTitle == $dbTitle) {
                            $found = $dbDistributorBranch;
                            break;
                        }

                        if (strpos($dbTitle, $distributorBranchTitle) !== false) {
                            $found = $dbDistributorBranch;
                            break;
                        }

                        $distributorTitleFirstPart = explode(' ', $distributorBranchTitle)[0];
                        if (strpos($dbTitle, $distributorTitleFirstPart) !== false) {

                            $found = $dbDistributorBranch;
                            break;
                        }

                        $distributorTitleFirstPart = explode('-', $distributorBranchTitle)[0];
                        if (strpos($dbTitle, $distributorTitleFirstPart) !== false) {
                            $found = $dbDistributorBranch;
                            break;
                        }
                    }

//                    dump($distributorTitleFirstPart);
//                    dump($dbTitle);
//                    dump($contract->getType());
//                    dump($contract->getContractNumber());
//                    die;
                    if (!$found) {
                        $unmatchedBranches[] = $distributorBranchTitle;
                    } else {
                        $matchedBranches[] = $distributorBranchTitle;
                        $contract->setDistributorBranchObject($found);
                        $this->em->persist($contract);
                        $this->em->flush($contract);
                    }
                }
            }


            // seller
            if ($sellerTitle) {
                $found = false;
                /** @var Seller $dbSeller */
                foreach ($dbSellers as $dbSeller) {
                    if (
                        ($contract->getType() == 'ENERGY' && $dbSeller->getOption() == SellerModel::OPTION_GAS) ||
                        ($contract->getType() == 'GAS' && $dbSeller->getOption() == SellerModel::OPTION_ENERGY)
                    ) {
                        continue;
                    }

                    $sellerTitle = mb_strtolower($sellerTitle);
                    $dbTitle = mb_strtolower($dbSeller->getTitle());

                    if ($sellerTitle == $dbTitle) {
                        $found = $dbSeller;
                        break;
                    }

                    if (mb_strpos($dbTitle, $sellerTitle) !== false) {
                        $found = $dbSeller;
                        break;
                    }



                    $sellerTitleFirstPart = explode(' ', $sellerTitle)[0];
                    if ($sellerTitleFirstPart == 'tauron') {
                        if ($dbTitle == mb_strtolower('TAURON Sprzedaż GZE Sp. z o.o.')) {
                            if (mb_strpos($sellerTitle, 'gze') !== false) {
                                $found = $dbSeller;
                                break;
                            }
                        }

                        if ($dbTitle == mb_strtolower('TAURON Sprzedaż Sp. z o.o.')) {
                            if (mb_strpos($sellerTitle, 'sprzeda') !== false) {
                                $found = $dbSeller;
                                break;
                            }
                        }
                    }

                    if ($dbTitle == mb_strtolower('Polski Prąd i Gaz Sp. z o.o.')) {
                        if (mb_strpos($sellerTitle, 'polski prąd') !== false) {
                            $found = $dbSeller;
                            break;
                        }
                    }

                    if ($dbTitle == mb_strtolower('PGNiG Obrót Detaliczny Sp. z o.o.')) {
                        if (mb_strpos($sellerTitle, 'pgnig obrót detaliczny') !== false) {
                            $found = $dbSeller;
                            break;
                        }

                        if (mb_strpos($sellerTitle, 'pgnig') !== false) {
                            $found = $dbSeller;
                            break;
                        }
                    }


                    if ($dbTitle == mb_strtolower('Energa Obrót S.A.')) {
                        $substring = substr($sellerTitle, 0, 6);
                        if ($substring == 'energa') {
                            $found = $dbSeller;
                            break;
                        }
                    }

                    if ($dbTitle == mb_strtolower('Hermes Energy Group S.A.')) {
                        $substring = substr($sellerTitle, 0, 6);
                        if ($substring == 'hermes') {
                            $found = $dbSeller;
                            break;
                        }
                    }

                    if ($dbTitle == mb_strtolower('PGE Obrót S.A.')) {
                        if (mb_strpos($sellerTitle, 'pge obrót') !== false) {
                            $found = $dbSeller;
                            break;
                        }

                        if (mb_strpos($sellerTitle, 'pge-obrót') !== false) {
                            $found = $dbSeller;
                            break;
                        }
                    }

                    if ($dbTitle == mb_strtolower('Orange Energia Sp. z o.o.')) {
                        if (mb_strpos($sellerTitle, 'orange energ') !== false) {
                            $found = $dbSeller;
                            break;
                        }
                    }

                    if ($dbTitle == mb_strtolower('Fortum Marketing and Sales Polska S.A.')) {
                        if (mb_strpos($sellerTitle, 'fortum') !== false) {
                            $found = $dbSeller;
                            break;
                        }
                    }

                    if ($dbTitle == mb_strtolower('Energy Match Sp. z o.o.')) {
                        if (mb_strpos($sellerTitle, 'energy match') !== false) {
                            $found = $dbSeller;
                            break;
                        }
                    }


                }

                if (!$found) {
                    $unmatchedCurrentSellers[] = $sellerTitle . ' ' . $contract->getType() . ' ' .$contract->getContractNumber();
                } else {
                    $matchedCurrentSellers[] = $sellerTitle;
                    $contract->setCurrentSellerObject($found);
                    $this->em->persist($contract);
                    $this->em->flush($contract);
                }
            }

            dump($index);
            $index++;
        }

        dump('Dystrybutor (znaleziono): ' . count($matchedDistributors));
        dump('Dystrybutor oddział (znaleziono): ' . count($matchedBranches));
        dump('Sprzedawca (znaleziono): ' . count($matchedCurrentSellers));

        dump('Dystrybutor (brak): ' . count($unmatchedDistributors));
        dump('Dystrybutor oddział (brak): ' . count($unmatchedBranches));
        dump('Sprzedawca (brak): ' . count($unmatchedCurrentSellers));


        dump($unmatchedDistributors);
        dump($unmatchedBranches);
        dump($unmatchedCurrentSellers);

    }

}