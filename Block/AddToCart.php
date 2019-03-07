<?php

namespace BelVG\AddToCart\Block;

use Magento\Framework\Data\Collection\AbstractDb;

/**
 * Class AddToCart
 * @package BelVG\AddToCart\Block
 */
class AddToCart extends \Magento\Backend\Block\Template
{
    /** @var \Magento\Quote\Model\ResourceModel\Quote\Collection $quoteCollection */
    private $quoteCollection;

    /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection */
    private $productCollection;

    /**
     * AddToCart constructor.
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection
     * @param \Magento\Quote\Model\ResourceModel\Quote\Collection $quoteCollection
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection,
        \Magento\Quote\Model\ResourceModel\Quote\Collection $quoteCollection,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        $this->productCollection = $productCollection;
        $this->quoteCollection = $quoteCollection;

        parent::__construct($context, $data);
    }

    /**
     * @return array
     */
    public function getCartsList()
    {
        $this->quoteCollection->addOrder('entity_id', AbstractDb::SORT_ORDER_ASC);

        $list = [];
        foreach ($this->quoteCollection as $quote) {
            /** @var \Magento\Quote\Model\Quote $quote */
            $list[$quote->getEntityId()] = $quote->getCustomerEmail() . ' [ID:' . $quote->getEntityId() . ']';
        }

        return $list;
    }

    /**
     * @return array
     */
    public function getProductsList()
    {
        $this->productCollection->addAttributeToSelect('name');
        $this->productCollection->addOrder('entity_id', AbstractDb::SORT_ORDER_ASC);

        $list = [];
        foreach ($this->productCollection as $product) {
            /** @var \Magento\Catalog\Model\Product $product */

            $haveOptions = in_array($product->getTypeId(), [
                \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE,
            ]);

            $list[$product->getEntityId()] = [
                'label' => $product->getName() . ' [ID: ' . $product->getEntityId() . ']',
                'have_options' => $haveOptions
            ];
        }

        return $list;
    }

    /**
     * @return string
     */
    public function getOptionsUrl()
    {
        return $this->getUrl('belvg_addtocart/index/options');
    }
}
