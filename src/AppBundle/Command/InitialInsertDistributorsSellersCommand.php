<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManager;
use GCRM\CRMBundle\Entity\Distributor;
use GCRM\CRMBundle\Entity\DistributorBranch;
use GCRM\CRMBundle\Entity\Seller;
use GCRM\CRMBundle\Service\SellerModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitialInsertDistributorsSellersCommand extends Command
{
    private $em;

    private $gasSellers = [
        'Alpiq Energy SE Spółka europejska Oddział w Polsce',
        'AOT Energy Poland Sp. z o.o.',
        'AUDAX ENERGIA Sp. z o.o.',
        'AVRIO MEDIA Sp. z o.o.',
        'Axpo Polska Sp. z o.o.',
        'Axpo Solution AG',
        'BD Spółka z o.o.',
        'Beskidzka Energetyka Sp. z o.o.',
        'Boryszew S.A.',
        'Ceramika Końskie Sp. z o.o.',
        'CEZ TRADE POLSKA Sp. z o.o.',
        'CRYOGAS M&T POLAND S.A.',
        'E2 energia Sp. z o.o.',
        'Efengaz Sp. z o.o.',
        'ELEKTRIX Sp. z o.o.',
        'Elgas Energy Sp. z o.o.',
        'ELSEN S.A.',
        'ENEA S.A.',
        'Enefit Sp. z o.o.',
        'Energa Obrót S.A.',
        'Energetyczne Centrum S.A.',
        'Energia dla firm S.A.',
        'ENERGIA PARK TRZEMOSZNA SP. Z O.O.',
        'Energia Polska Sp. z o.o.',
        'EnergiaOK Sp. z o.o.',
        'ENERGO OPERATOR Sp. z o.o.',
        'Energomedia Sp. z o.o.',
        'Energy Match Sp. z o.o.',
        'ENGIE Zielona Energia Sp. z o.o.',
        'ENIGA Edward Zdrojek',
        'ENREX ENERGY SP Z O.O.',
        'ESV WISŁOSAN Sp. z o.o.',
        'EWE Polska Sp. z o.o.',
        'FITEN SA',
        'Fortum Marketing and Sales Polska S.A.',
        'GASELLE Sp. z o.o.',
        'Gaspol S.A.',
        'GET EnTra Sp. z o.o.',
        'GLOSBE Sp. z o.o.',
        'Green S.A. w restrukturyzacji',
        'Hadex-Gaz Ziemny Sp. z o.o.',
        'HANDEN SP. z o.o.',
        'innogy Polska S.A.',
        'Nida Media Sp. z o.o.',
        'NOVUM S.A.',
        'ONE S.A.',
        'Onico Energia Sp. z o.o. S.K.A.',
        'Orange Energia sp. z o.o.',
        'ORLEN Paliwa Sp. z o.o.',
        'OZE ENERGY Sp. z o.o.',
        'PAK-Volt S.A.',
        'PGE Obrót S.A.',
        'PGE Polska Grupa Energetyczna S.A.',
        'PGNiG Obrót Detaliczny Sp. z o.o.',
        'PGNiG S.A.',
        'PGNiG Supply & Trading Gmbh',
        'Po Prostu Energia S.A.',
        'Polenergia Kogeneracja sp. z o.o.',
        'Polenergia Obrót SA',
        'Polkomtel Business Development Sp. z o.o.',
        'Polkomtel Sp. z o.o.',
        'POLMAX S.A. S.K.A.',
        'Polski Koncern Naftowy ORLEN S.A.',
        'Polski Prąd i Gaz Sp. z o.o.',
        'Polskie Przedsiębiorstwo Energetyczne Konerg Spółka Akcyjna',
        'Proton Polska Energia Sp. z o.o.',
        'Przedsiębiorstwo Gospodarki Komunalnej Daszyna Sp. z o.o.',
        'Pulsar Energia Sp. z o.o.',
        'SATOR Marek Szymkowiak',
        'SIME POLSKA Sp. z o. o.',
        'TAURON Polska Energia S.A.',
        'Tauron Sprzedaż Sp. z o.o.',
        'UNIMOT ENERGIA I GAZ Sp. z o.o.',
        'UNIMOT S.A.',
        'UP Energy Sp. z o.o.',
        'Veolia Energia Polska S.A.',
        'VERVIS Sp. z o.o.',
        'Zakład Dostaw Nośników Energetycznych Sp. z o.o.',
        'Zakłady Urządzeń Chemicznych i Armatury Przemysłowej "Chemar" S.A.',
        'Hermes Energy Group S.A.',
    ];

    private $energySellers = [
        'Energy Match Sp. z o.o.',
        'TAURON Sprzedaż GZE Sp. z o.o.',
        'PKP Energetyka S.A.',
        'Veolia Energia Polska S.A.',
        'ENERGA-OBRÓT S.A.',
        'CEZ Polska Sp. z o.o.',
        'PGE Energia Ciepła S.A.',
        'FITEN S.A.',
        'TAURON Sprzedaż Sp. z o.o.',
        'PGE Obrót S.A.',
        'Enea Elektrownia Połaniec S.A.',
        'Slovenské Elektrárne, a.s. Spółka Akcyjna Oddział w Polsce',
        'Alpiq Energy Spółka europejska Oddział w Polsce',
        'Tauron Polska Energia S.A.',
        'ESV S.A.',
        'Elektrix S.A.',
        '3 WINGS Sp. z o.o.',
        'ENIGA Edward Zdrojek',
        'ProPower 21 Sp. z o.o.',
        'Fortum Marketing and Sales Polska S.A.',
        'ERGO ENERGY Sp. z o.o.',
        'Inter Energia S.A.',
        'TRADEA Sp. z o.o.',
        'POLENERGIA OBRÓT S.A.',
        'Axpo Solutions A.G.',
        'Novum S.A.',
        'PAK-Volt S.A.',
        'PGNiG S.A',
        'Green S.A. w restrukturyzacji',
        'Energo Operator Sp. z o.o.',
        'POLENERGIA Dystrybucja Sp. z o.o.',
        'Polski Prąd i Gaz Sp. z o.o.',
        'E2 Energia Sp. z o.o.',
        'ENERHA Sp. z o.o.',
        'Audax Energia Sp. z o.o.',
        'Synergia Polska Energia Sp. z o.o.',
        'EWE Energia Sp. z o.o.',
        'Energia Euro Park sp. z o.o.',
        'Elektra S.A',
        'Gaspol S.A.',
        'Towarzystwo Inwestycyjne „Elektrownia Wschód” S.A.',
        'Edon Sp. z o.o.',
        'Polkomtel Sp. z o.o.',
        'Grupa PSB Handel S.A.',
        'Orange Energia Sp. z o.o.',
        'GPEC Energia Sp. z o.o. w upadłości',
        'PGNiG Obrót Detaliczny Sp. z o.o.',
        'VERVIS Sp. z o.o.',
        'Elektrociepłownia Mielec sp. z o.o.',
        'Orange Polska S.A.',
        'Energomedia Sp. z o.o.',
        'PGNiG Termika S.A.',
        'Polski Koncern Naftowy ORLEN S.A.',
        'Szczecińska Energetyka Cieplna Sp. z o.o.',
        'InfoEngine S.A.',
        'Axpo Polska Sp. z o.o.',
        'Energia Polska Sp. z o.o.',
        'Grupa Energia Obrót GE Sp. z o.o. Sp. k.',
        'Grupa Energia GE Sp. z o.o. Sp. k.',
        'WM Malta Sp. z o.o.',
        'Energetyka Cieplna Opolszczyzny S.A.',
        'IRL Polska Sp. z o.o.',
        'EnergiaOK Sp. z o.o.',
        'UNIMOT ENERGIA I GAZ Sp. z o.o.',
        'Roko Sp. z o.o.',
        'PGB Dystrybucja Sp. z o.o.',
        'Nida Media Sp. z o.o.',
        'ENEA S.A.',
        'ESV Wisłosan Sp. z o.o.',
        'Enrex Energy Sp. z o.o.',
        'Orlen Paliwa Sp. z o.o.',
        'IEN ENERGY Sp. z o.o.',
        'CORRENTE Dla Domu Sp. z o.o. Sp. k.',
        'Meon Energy Sp. z o.o.',
        'Handen Sp. z o.o.',
        'HEXA Telecom Sp. z o.o.',
        'Zakład Energetyczny Użyteczności Publicznej S.A.',
        'ENGIE Zielona Energia Sp. z o.o.',
        'TRMEW Obrót S.A.',
        'Polskie Przedsiębiorstwo Energetyczne KONERG S.A.',
        'Tańsza Energia Konsultanci Energetyczni Sp. z o.o.',
        'Vortex Energy Obrót Sp. z o. o.',
        'Technika Energetyczna Sp. z o.o.',
        'OZE Energy Sp. z o.o.',
        'GasoEnergia Polskie Zakłady Energetyczne Sp. z o.o.',
        'Energia i GAZ Sp. z o.o.',
        'D-Energia Sp. z o.o.',
        'EZO Trading S.A.',
        'Next Kraftwerke GmbH',
        'Pulsar Energia Sp. z o.o.',
        'SIME Polska Sp. z o.o.',
        'Green Light Obrót Sp. z o.o.',
        'Przedsiębiorstwo Energetyki Cieplnej “Legionowo” Sp. z o.o.',
        'CONTROL PROCESS S.A.',
        'Po Prostu Energia S.A.',
        'ONE S.A.',
        'Hermes Energy Group S.A.',
        'ENEFIT Sp. z o.o.',
        'GET EnTra  Sp. z o.o.',
        'innogy Polska S.A.',
        'POLENERGIA Dystrybucja Sp. z o.o.',
        'Energia Polska Sp. z o.o.',
        'ENIGA Edward Zdrojek',
        'Energa Obrót S.A.',
        'Orange Polska S.A.',
        'ENEA S.A.',
        'PGE Obrót S.A.',
        'ENERHA Sp. z o.o.',
        'VERVIS Sp. z o .o.',
        'PGE Centrum Sp. z o.o.',
        'Geon Sp. z o.o.',
        'Green Lights Dystrybucja Sp. z o. o.',
        'Green Lights Sp. z o.o.',
        'Green Lights Holding Sp. z o.o.',
        'Plus Energia Sp. z o.o.',
        'Entrade Sp. z o.o.',
        'EHN S.A.',
        'Polska Energia Sp. z o.o.',
        'Sator Marek Szymkowiak',
        'Ignitis Polska Sp. z o.o.',
        'EDP Energia Polska Sp. z o.o.',
        'HGC Gretna Investments Sp. z o.o. S.J.',
        'EWE energia Sp. z o.o.',
        'Axpo Polska Sp. z o.o.',
        'INTRENCO Sp. z o.o.',
        'Energy Gate Europe Sp. z o.o.',
    ];

    private $distributors = [
        'Innogy Stoen Operator Sp. z o.o.', // 1
        'Enea S.A.', // 2
        'PGE Dystrybucja S.A.', // 3
        'Tauron Dystrybucja S.A.', // 4
        'Energa Operator S.A.', // 5
    ];

    private $distributorBranches = [
        3 => [
            'Białystok',
            'Lublin',
            'Łódź Miasto',
            'Łódź Teren',
            'Rzeszów',
            'Skarżysko-Kamienna',
            'Warszawa',
            'Zamość',
        ],
        4 => [
            'Będzin',
            'Bielsko-Biała',
            'Częstochowa',
            'Gliwice',
            'Jelenia Góra',
            'Kraków',
            'Legnica',
            'Opole',
            'Tarnów',
            'Wałbrzych',
            'Wrocław',
        ]
    ];

    public function __construct(EntityManager $em)
    {
        $this->em = $em;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('appbundle:initial-insert-distributors-sellers');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        foreach ($this->gasSellers as $gasSeller) {
            // to avoid duplicates
            if ($this->em->getRepository('GCRMCRMBundle:Seller')->findOneBy([
                'title' => $gasSeller,
                'option' => SellerModel::OPTION_GAS
            ])) {
                continue;
            }

            $seller = new Seller();
            $seller->setTitle($gasSeller);
            $seller->setOption(SellerModel::OPTION_GAS);

            $this->em->persist($seller);
            $this->em->flush($seller);
        }

        foreach ($this->energySellers as $energySeller) {
            // to avoid duplicates
            if ($this->em->getRepository('GCRMCRMBundle:Seller')->findOneBy([
                'title' => $energySeller,
                'option' => SellerModel::OPTION_ENERGY
            ])) {
                continue;
            }

            $seller = new Seller();
            $seller->setTitle($energySeller);
            $seller->setOption(SellerModel::OPTION_ENERGY);

            $this->em->persist($seller);
            $this->em->flush($seller);
        }

        foreach ($this->distributors as $distributorTitle) {
            // to avoid duplicates
            if ($this->em->getRepository('GCRMCRMBundle:Distributor')->findOneBy([
                'title' => $distributorTitle,
            ])) {
                continue;
            }

            $distributor = new Distributor();
            $distributor->setTitle($distributorTitle);

            $this->em->persist($distributor);
            $this->em->flush($distributor);
        }

        foreach ($this->distributorBranches as $distributorId => $distributorBranches) {
            foreach ($distributorBranches as $distributorBranchTitle) {
                // to avoid duplicates
                if ($this->em->getRepository('GCRMCRMBundle:DistributorBranch')->findOneBy([
                    'title' => $distributorBranchTitle,
                ])) {
                    continue;
                }

                $distributor = $this->em->getRepository('GCRMCRMBundle:Distributor')->find($distributorId);
                if (!$distributor) {
                    dump('ERROR - distributor not found');
                    continue;
                }

                $distributorBranch = new DistributorBranch();
                $distributorBranch->setDistributor($distributor);
                $distributorBranch->setTitle($distributorBranchTitle);

                $this->em->persist($distributorBranch);
                $this->em->flush($distributorBranch);
            }
        }

        dump('Success');
    }

}