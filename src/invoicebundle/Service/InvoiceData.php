<?php

namespace Wecoders\InvoiceBundle\Service;

class InvoiceData
{
    const TYPE_PRODUCTS_CODE = 1;
    const TYPE_RABATE_CODE = 2;

    private $nr;

    /** @var  \DateTime */
    private $date;

    private $createdInCity;

    /** @var  InvoicePerson */
    private $seller;

    /** @var  InvoicePerson */
    private $client;

    private $productGroups;

    private $logo;

    private $dateOfPayment;

    private $customData;

    private $balanceBeforeInvoice = 0;

    private $directoryOutput;

    private $filename;

    private $helper;

    public function getDateOfPayment()
    {
        return $this->dateOfPayment;
    }

    public function setDateOfPayment($dateOfPayment)
    {
        $this->dateOfPayment = $dateOfPayment;

        return $this;
    }

    public function getNr()
    {
        return $this->nr;
    }

    public function getSeller()
    {
        return $this->seller;
    }

    public function setSeller(InvoicePerson $seller)
    {
        $this->seller = $seller;

        return $this;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function setClient(InvoicePerson $client)
    {
        $this->client = $client;

        return $this;
    }

    public function setNr($nr)
    {
        $this->nr = $nr;

        return $this;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate(\DateTime $date)
    {
        $this->date = $date;

        return $this;
    }

    public function getProductGroups()
    {
        return $this->productGroups;
    }

    public function setProductGroups($productGroups)
    {
        $productGroups = $this->removeProductDuplicates($productGroups);
        $this->productGroups = $productGroups;

        return $this;
    }

    public function getLogo()
    {
        return $this->logo;
    }

    public function setLogo($logo)
    {
        $this->logo = $logo;

        return $this;
    }

    public function getCustomData()
    {
        return $this->customData;
    }

    public function setCustomData($customData)
    {
        $this->customData = $customData;

        return $this;
    }

    public function getBalanceBeforeInvoice()
    {
        return $this->balanceBeforeInvoice;
    }

    public function setBalanceBeforeInvoice($balanceBeforeInvoice)
    {
        $this->balanceBeforeInvoice = number_format($balanceBeforeInvoice, 2, '.', '');

        return $this;
    }

    public function getCreatedInCity()
    {
        return $this->createdInCity;
    }

    public function setCreatedInCity($createdInCity)
    {
        $this->createdInCity = $createdInCity;

        return $this;
    }

    public function getDirectoryOutput()
    {
        return $this->directoryOutput;
    }

    public function setDirectoryOutput($directoryOutput)
    {
        $this->directoryOutput = $directoryOutput;

        return $this;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    public function __construct(Helper $helper)
    {
        $this->helper = $helper;
    }

    public function getProductsGroupSummaryNetValue($products)
    {
        $result = 0;

        if ($products) {
            /** @var InvoiceProduct $product */
            foreach ($products as $product) {
                $result += number_format($product->getNetValue(), 2, '.', '');
            }
        }

        return number_format($result, 2, '.', '');
    }

    public function getProductsFirstGroupVatPercentageKey()
    {
        $groups = $this->getProductsGroupsSummaryGroupedByVat();

        foreach ($groups['data'] as $key => $data) {
            return $key;
        }
        return 0;
    }

    public function getProductsGroupSummaryVatValue($products)
    {
        $result = 0;

        if ($products) {
            /** @var InvoiceProduct $product */
            foreach ($products as $product) {
                $result += number_format($product->getVatValue(), 2, '.', '');
            }
        }

        return number_format($result, 2, '.', '');
    }

    public function getProductsGroupSummary($products)
    {
        $result = 0;

        if ($products) {
            /** @var InvoiceProduct $product */
            foreach ($products as $product) {
                $result += number_format($product->getGrossValue(), 2, '.', '');
            }
        }

        return number_format($result, 2, '.', '');
    }

    public function getProductsGroupsSummaryGroupedByVat()
    {
        $result = [
            'data' => null,
            'dataRabates' => null,
            'summaryProducts' => [
                'netValue' => 0,
                'vatValue' => 0,
                'grossValue' => 0,
            ],
            'summaryRabates' => [
                'netValue' => 0,
                'vatValue' => 0,
                'grossValue' => 0,
            ],
            'summary' => [
                'netValue' => 0,
                'vatValue' => 0,
                'grossValue' => 0,
            ],
        ];

        $productGroups = $this->getProductGroups();

        if (!$productGroups)  {
            return $result;
        }

        $vatGroups = [];
        $vatRabateGroups = [];

        /** @var InvoiceProductGroup $productGroup */
        foreach ($productGroups as $productGroup) {
            $products = $productGroup->getProducts();
            if ($products) {
                /** @var InvoiceProduct $product */
                foreach ($products as $product) {
                    $vatPercentage = $product->getVatPercentage() && is_numeric($product->getVatPercentage()) ? $product->getVatPercentage() : 0;
                    if (!key_exists($vatPercentage, $vatGroups)) {
                        $vatGroups[$vatPercentage] = [
                            'vatPercentage' => $vatPercentage,
                            'netValue' => 0,
                            'vatValue' => 0,
                            'grossValue' => 0,
                        ];
                    }

                    $vatGroups[$vatPercentage]['netValue'] += number_format($product->getNetValue(), 2, '.', '');
                    if (!$vatPercentage) {
                        $vatGroups[$vatPercentage]['grossValue'] += number_format($product->getGrossValue(), 2, '.', '');
                    }
                }
            }

            $rabates = $productGroup->getRabates();
            if ($rabates) {
                /** @var InvoiceProduct $product */
                foreach ($rabates as $product) {
                    $vatPercentage = $product->getVatPercentage() && is_numeric($product->getVatPercentage()) ? $product->getVatPercentage() : 0;
                    if (!key_exists($vatPercentage, $vatRabateGroups)) {
                        $vatRabateGroups[$vatPercentage] = [
                            'vatPercentage' => $vatPercentage,
                            'netValue' => 0,
                            'vatValue' => 0,
                            'grossValue' => 0,
                        ];
                    }

                    $vatRabateGroups[$vatPercentage]['netValue'] += number_format($product->getNetValue(), 2, '.', '');
                    if (!$vatPercentage) {
                        $vatRabateGroups[$vatPercentage]['grossValue'] += number_format($product->getGrossValue(), 2, '.', '');
                    }
                }
            }
        }

        // calculate vat and gross value for vat percentage > 0
        foreach ($vatGroups as $key => $vatGroup) {
            if ($vatGroup['vatPercentage'] > 0) {
                $vatGroups[$key]['grossValue'] = $this->helper->calculateGrossValue($vatGroup['netValue'], $vatGroup['vatPercentage']);
                $vatGroups[$key]['vatValue'] = $this->helper->calculateVatValue($vatGroup['netValue'], $vatGroups[$key]['grossValue'], $vatGroup['vatPercentage']);
            }
        }
        $result['data'] = $vatGroups;

        foreach ($vatRabateGroups as $key => $vatGroup) {
            if ($vatGroup['vatPercentage'] > 0) {
                $vatRabateGroups[$key]['grossValue'] = $this->helper->calculateGrossValue($vatGroup['netValue'], $vatGroup['vatPercentage']);
                $vatRabateGroups[$key]['vatValue'] = $this->helper->calculateVatValue($vatGroup['netValue'], $vatRabateGroups[$key]['grossValue'], $vatGroup['vatPercentage']);
            }
        }
        $result['dataRabates'] = $vatRabateGroups;

        if (count($result['data'])) {
            foreach ($result['data'] as $key => $data) {
                $result['summary']['netValue'] += $data['netValue'];
                $result['summary']['grossValue'] += $data['grossValue'];
                $result['summary']['vatValue'] += $data['vatValue'];

                $result['summaryProducts']['netValue'] += $data['netValue'];
                $result['summaryProducts']['grossValue'] += $data['grossValue'];
                $result['summaryProducts']['vatValue'] += $data['vatValue'];
            }
            if (count($result['dataRabates'])) {
                foreach ($result['dataRabates'] as $key => $data) {
                    $result['summary']['netValue'] -= $data['netValue'];
                    $result['summary']['grossValue'] -= $data['grossValue'];
                    $result['summary']['vatValue'] -= $data['vatValue'];

                    $result['summaryRabates']['netValue'] += $data['netValue'];
                    $result['summaryRabates']['grossValue'] += $data['grossValue'];
                    $result['summaryRabates']['vatValue'] += $data['vatValue'];
                }
            }
        }
        $result['summaryProducts']['netValue'] = number_format($result['summaryProducts']['netValue'], 2, '.', '');
        $result['summaryProducts']['grossValue'] = number_format($result['summaryProducts']['grossValue'], 2, '.', '');
        $result['summaryProducts']['vatValue'] = number_format($result['summaryProducts']['vatValue'], 2, '.', '');

        $result['summaryRabates']['netValue'] = number_format($result['summaryRabates']['netValue'], 2, '.', '');
        $result['summaryRabates']['grossValue'] = number_format($result['summaryRabates']['grossValue'], 2, '.', '');
        $result['summaryRabates']['vatValue'] = number_format($result['summaryRabates']['vatValue'], 2, '.', '');

        $result['summary']['netValue'] = number_format($result['summary']['netValue'], 2, '.', '');
        $result['summary']['grossValue'] = number_format($result['summary']['grossValue'], 2, '.', '');
        $result['summary']['vatValue'] = number_format($result['summary']['vatValue'], 2, '.', '');

        return $result;
    }

    public function getProductsGroupsSummaryNetValue()
    {
        $result = 0;

        $productGroups = $this->getProductGroups();

        /** @var InvoiceProductGroup $productGroup */
        foreach ($productGroups as $productGroup) {
            $result += $this->getProductsGroupSummaryNetValue($productGroup->getProducts());
        }

        return number_format($result, 2, '.', '');
    }

    public function getProductsGroupsSummaryVatValue()
    {
        $result = 0;

        $productGroups = $this->getProductGroups();

        /** @var InvoiceProductGroup $productGroup */
        foreach ($productGroups as $productGroup) {
            $result += $this->getProductsGroupSummaryVatValue($productGroup->getProducts());
        }

        return number_format($result, 2, '.', '');
    }

    public function getProductsGroupsSummary()
    {
        $result = 0;

        $productGroups = $this->getProductGroups();

        /** @var InvoiceProductGroup $productGroup */
        foreach ($productGroups as $productGroup) {
            $result += $this->getProductsGroupSummary($productGroup->getProducts());
        }

        return number_format($result, 2, '.', '');
    }

    public function getProductsTypeSummaryNetValue($typeCode)
    {
        $result = 0;

        $products = $this->determineProducts($typeCode);
        if ($products) {
            /** @var InvoiceProduct $product */
            foreach ($products as $product) {
                $result += $product->getNetValue();
            }
        }

        return number_format($result, 2, '.', '');
    }

    public function getProductsTypeSummaryVatValue($typeCode)
    {
        $result = 0;

        /** @var InvoiceProduct $product */
        $products = $this->determineProducts($typeCode);
        if ($products) {
            foreach ($products as $product) {
                $result += $product->getVatValue();
            }
        }

        return number_format($result, 2, '.', '');
    }

    public function getProductsTypeSummary($typeCode)
    {
        $result = 0;

        $products = $this->determineProducts($typeCode);
        if ($products) {
            /** @var InvoiceProduct $product */
            foreach ($products as $product) {
                $result += $product->getGrossValue();
            }
        }

        return number_format($result, 2, '.', '');
    }

    public function getTotalSummary()
    {
//        $result = $this->getProductsTypeSummary(self::TYPE_PRODUCTS_CODE) - $this->getProductsTypeSummary(self::TYPE_RABATE_CODE) + $this->balanceBeforeInvoice;
        $vatGroups = $this->getProductsGroupsSummaryGroupedByVat();
        $result = $vatGroups['summary']['grossValue'] - $this->getProductsTypeSummary(self::TYPE_RABATE_CODE) + $this->balanceBeforeInvoice;

        if ($result > 0) {
            return number_format($result, 2, '.', '');
        }
        return 0;
    }

    public function getPriceInWords($price)
    {
        return \Numbers_Words::toCurrency(number_format(($price), 2, '.', ''), 'pl');
    }

    private function determineProducts($typeCode)
    {
        if ($typeCode != self::TYPE_PRODUCTS_CODE && $typeCode != self::TYPE_RABATE_CODE) {
            return null;
        }

        $products = [];

        $productGroups = $this->getProductGroups();
        if ($productGroups) {
            /** @var InvoiceProductGroup $productGroup */
            foreach ($productGroups as $productGroup) {
                if ($typeCode == self::TYPE_PRODUCTS_CODE) {
                    $tmpProducts = $productGroup->getProducts();
                } else {
                    $tmpProducts = $productGroup->getRabates();
                }

                if (!is_array($tmpProducts)) {
                    continue;
                }

                /** @var InvoiceProduct $product */
                foreach ($tmpProducts as $product) {
                    $products[] = $product;
                }
            }
        }

        if (!count($products)) {
            $products = null;
        }

        return $products;
    }

    private function removeProductDuplicates($productGroups)
    {
        if (!$productGroups) {
            return $productGroups;
        }

        $productsUniqueIds = [];

        // remove items that are the same after first attempt

        /** @var InvoiceProductGroup $invoiceProductGroup */
        foreach ($productGroups as $invoiceProductGroup) {
            $products = $invoiceProductGroup->getProducts();
            if ($products) {
                /** @var InvoiceProduct $invoiceProduct */
                foreach ($products as $keyProduct => $invoiceProduct) {
                    if ($invoiceProduct->getIsUnique() && $invoiceProduct->getOriginId()) {
                        if (!in_array($invoiceProduct->getOriginId(), $productsUniqueIds)) {
                            $productsUniqueIds[] = $invoiceProduct->getOriginId();
                        } else {
                            // unset product duplicate
                            $invoiceProductGroup->removeSingleProductByOriginId($invoiceProduct->getOriginId());
                        }
                    }
                }
            }
        }

        // remove productGroups if are empty (no products and no rabates)
        $filteredProductGroups = [];
        /** @var InvoiceProductGroup $invoiceProductGroup */
        foreach ($productGroups as $invoiceProductGroup) {
            $products = $invoiceProductGroup->getProducts();
            $rabates = $invoiceProductGroup->getRabates();

            if (($products && is_array($products) && count($products)) || ($rabates && is_array($rabates) && count($rabates))) {
                $filteredProductGroups[] = $invoiceProductGroup;
            }
        }

        return $filteredProductGroups;
    }
}