<?php

namespace Wecoders\InvoiceBundle\Service;

class InvoiceProductGroup
{
    private $id;

    /**
     * It's a group title. Can be for example category name, telephone number or even contract number
     * @var string
     */
    private $title;

    private $products;

    private $rabates;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    public function getProducts()
    {
        return $this->products;
    }

    public function setProducts($products)
    {
        $this->products = $products;

        return $this;
    }

    public function removeSingleProductByOriginId($originId)
    {
        if ($this->products) {
            $newProducts = [];
            /** @var InvoiceProduct $invoiceProduct */
            $omittedFirstProductWithOriginId = false;
            foreach ($this->products as $invoiceProduct) {
                if (!$omittedFirstProductWithOriginId && $invoiceProduct->getOriginId() == $originId) {
                    $omittedFirstProductWithOriginId = true;
                    continue;
                }

                $newProducts[] = $invoiceProduct;
            }

            $this->setProducts($newProducts);
        }
    }

    /**
     * @return mixed
     */
    public function getRabates()
    {
        return $this->rabates;
    }

    /**
     * @param mixed $rabates
     */
    public function setRabates($rabates)
    {
        $this->rabates = $rabates;
    }
}