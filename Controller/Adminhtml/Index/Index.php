<?php

namespace BelVG\AddToCart\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class Index
 * @package BelVG\AddToCart\Controller\Adminhtml\Index
 */
class Index extends Action
{
    const ACL_RESOURCE = 'BelVG_AddToCart::add';

    /** @var \Magento\Quote\Model\QuoteRepository $quoteRepository */
    private $quoteRepository;

    /** @var \Magento\Catalog\Model\ProductRepository $productRepository */
    private $productRepository;


    /**
     * Index constructor.
     * @param Action\Context $context
     */
    public function __construct(
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Catalog\Model\Product\Attribute\Repository $attributeRepository,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->productRepository = $productRepository;
        $this->attributeRepository = $attributeRepository;

        parent::__construct($context);
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        $result = parent::_isAllowed();
        $result = $result && $this->_authorization->isAllowed(self::ACL_RESOURCE);
        return $result;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $request = $this->getRequest();
        if ($request->getParam('submit') == 'add') {

            $cartId = $request->getParam('cart_id');
            $productId = $request->getParam('product_id');
            $sku = $request->getParam('sku');
            $qty = $request->getParam('qty');

            $quote = $this->quoteRepository->get($cartId);
            $product = $this->productRepository->getById($productId);

            if ($quote && $product) {
                if ($product->getTypeId() == \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE) {
                    /** @var \Magento\GroupedProduct\Model\Product\Type\Grouped $typedProduct */
                    $typedProduct = $product->getTypeInstance();
                    foreach ($typedProduct->getChildrenIds($productId, false) as $children) {
                        foreach ($children as $id) {
                            $_product = $this->productRepository->getById($id);
                            $quote->addProduct($_product, $this->makeAddRequest($_product, $sku, $qty));
                        }
                    }
                } else {
                    $quote->addProduct($product, $this->makeAddRequest($product, $sku, $qty));
                }
                $this->quoteRepository->save($quote);
                $this->messageManager->addErrorMessage(__('Add to cart successful.'));
            } else {
                $this->messageManager->addErrorMessage(__('Add to cart fail.'));
            }

            $this->_redirect('belvg_addtocart/index/index');
        }

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Magento_Customer::customer');
        $resultPage->getConfig()->getTitle()->prepend(__('Add to cart'));
        return $resultPage;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param null $sku
     * @param int $qty
     * @return \Magento\Framework\DataObject
     */
    private function makeAddRequest(\Magento\Catalog\Model\Product $product, $sku = null, $qty = 1)
    {
        $data = [
            'product' => $product->getEntityId(),
            'qty' => $qty
        ];

        switch ($product->getTypeId()) {
            case \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE:
                $data = $this->setConfigurableRequestOptions($product, $sku, $data);
                break;
            case \Magento\Bundle\Model\Product\Type::TYPE_CODE:
                $data = $this->setBundleRequestOptions($product, $data);
                break;
        }

        $request = new \Magento\Framework\DataObject();
        $request->setData($data);

        return $request;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param $sku
     * @param array $data
     * @return array
     */
    private function setConfigurableRequestOptions(\Magento\Catalog\Model\Product $product, $sku, array $data)
    {
        /** @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable $typedProduct */
        $typedProduct = $product->getTypeInstance();

        $childProduct = $this->productRepository->get($sku);
        $productAttributeOptions = $typedProduct->getConfigurableAttributesAsArray($product);

        $superAttributes = [];
        foreach ($productAttributeOptions as $option) {
            $superAttributes[$option['attribute_id']] = $childProduct->getData($option['attribute_code']);
        }

        $data['super_attribute'] = $superAttributes;
        return $data;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param array $data
     * @return array
     */
    private function setBundleRequestOptions(\Magento\Catalog\Model\Product $product, array $data)
    {
        /** @var \Magento\Bundle\Model\Product\Type $typedProduct */
        $typedProduct = $product->getTypeInstance();

        $selectionCollection = $typedProduct->getSelectionsCollection($typedProduct->getOptionsIds($product), $product);

        $options = [];
        foreach ($selectionCollection as $proselection) {
            $options[$proselection->getOptionId()] = $proselection->getSelectionId();
        }

        $data['bundle_option'] = $options;
        return $data;
    }
}
