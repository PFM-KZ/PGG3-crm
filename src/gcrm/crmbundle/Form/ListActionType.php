<?php

namespace GCRM\CRMBundle\Form;

use GCRM\CRMBundle\Entity\Client;
use GCRM\CRMBundle\Entity\ClientEnquiry;
use GCRM\CRMBundle\Entity\Invoice;
use GCRM\CRMBundle\Entity\InvoiceCorrection;
use GCRM\CRMBundle\Entity\InvoiceProforma;
use GCRM\CRMBundle\Entity\Payment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wecoders\EnergyBundle\Entity\InvoiceCollective;
use Wecoders\EnergyBundle\Entity\InvoiceEstimatedSettlement;
use Wecoders\EnergyBundle\Entity\InvoiceEstimatedSettlementCorrection;
use Wecoders\EnergyBundle\Entity\InvoiceProformaCorrection;
use Wecoders\EnergyBundle\Entity\InvoiceSettlement;
use Wecoders\EnergyBundle\Entity\InvoiceSettlementCorrection;
use Wecoders\EnergyBundle\Service\ListSearcher\InvoiceCorrectionEnergy;
use GCRM\CRMBundle\Service\Exporter\ClientEnquiryDataFilter;

class ListActionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // default settings
        $builder
            ->add('downloadXlsx', SubmitType::class, ['label' => 'Pobierz do Xlsx', 'attr' => ['class' => 'btn btn-secondary btn-sm']])
        ;
        $builder
            ->add('downloadCsv', SubmitType::class, ['label' => 'Pobierz do CSV', 'attr' => ['class' => 'btn btn-secondary btn-sm']])
        ;

        if ($options['data']['entityClass'] == Client::class) {
            $builder
//                ->add('downloadXlsxPriceLists', SubmitType::class, ['label' => 'Xlsx - cenniki', 'attr' => ['class' => 'btn btn-secondary btn-sm']])
                ->add('downloadCsvPriceLists', SubmitType::class, ['label' => 'Csv - cenniki', 'attr' => ['class' => 'btn btn-secondary btn-sm']])
//                ->add('downloadXlsxSellerTariffs', SubmitType::class, ['label' => 'Xlsx - taryfy sprzedawcy', 'attr' => ['class' => 'btn btn-secondary btn-sm']])
                ->add('downloadCsvSellerTariffs', SubmitType::class, ['label' => 'Csv - taryfy sprzedawcy', 'attr' => ['class' => 'btn btn-secondary btn-sm']])
//                ->add('downloadXlsxDistributionTariffs', SubmitType::class, ['label' => 'Xlsx - taryfy dystrybucji', 'attr' => ['class' => 'btn btn-secondary btn-sm']])
                ->add('downloadCsvDistributionTariffs', SubmitType::class, ['label' => 'Csv - taryfy dystrybucji', 'attr' => ['class' => 'btn btn-secondary btn-sm']])
//                ->add('downloadXlsxPp', SubmitType::class, ['label' => 'Xlsx - PP', 'attr' => ['class' => 'btn btn-secondary btn-sm']])
                ->add('downloadCsvPp', SubmitType::class, ['label' => 'Csv - PP', 'attr' => ['class' => 'btn btn-secondary btn-sm']])
            ;
        }

        if ($options['data']['entityClass'] == Client::class) {
            $builder
                ->add('downloadXlsxTerminationFormat', SubmitType::class, ['label' => 'Pobierz do Xlsx (format wypowiedzenia)', 'attr' => ['class' => 'btn btn-secondary btn-sm']])
            ;
            $builder
                ->add('downloadCsvTerminationFormat', SubmitType::class, ['label' => 'Pobierz do CSV (format wypowiedzenia)', 'attr' => ['class' => 'btn btn-secondary btn-sm']])
            ;
            $builder
                ->add('downloadXlsContractorsOptima', SubmitType::class, ['label' => 'Kontrahenci - pobierz do Xls (Optima)', 'attr' => ['class' => 'btn btn-secondary btn-sm']])
            ;
        }

        if ($options['data']['entityClass'] == Invoice::class) {
            $builder
                ->add('downloadFiles', SubmitType::class, ['label' => 'Pobierz pliki (.zip)', 'attr' => ['class' => 'btn btn-secondary btn-sm']])
            ;
        }

        // Download optima documents format
        if (
            $options['data']['entityClass'] == \Wecoders\EnergyBundle\Entity\Invoice::class ||
            $options['data']['entityClass'] == InvoiceCorrectionEnergy::class ||
            $options['data']['entityClass'] == \Wecoders\EnergyBundle\Entity\InvoiceCorrection::class ||
            $options['data']['entityClass'] == InvoiceSettlement::class ||
            $options['data']['entityClass'] == InvoiceSettlementCorrection::class ||
            $options['data']['entityClass'] == InvoiceEstimatedSettlement::class ||
            $options['data']['entityClass'] == InvoiceEstimatedSettlementCorrection::class ||
            $options['data']['entityClass'] == \Wecoders\EnergyBundle\Entity\InvoiceProforma::class ||
            $options['data']['entityClass'] == InvoiceProformaCorrection::class ||
            $options['data']['entityClass'] == InvoiceCollective::class ||
            $options['data']['entityClass'] == Payment::class
        ) {
            $builder
                ->add('downloadCsvOptima', SubmitType::class, ['label' => 'Pobierz do CSV (Optima)', 'attr' => ['class' => 'btn btn-secondary btn-sm']])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null,
        ));
    }
}
