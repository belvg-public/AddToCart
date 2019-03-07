<?php

namespace BelVG\AddToCart\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;

/**
 * Class Options
 * @package BelVG\AddToCart\Controller\Adminhtml\Index
 */
class Options extends Action
{
    const ACL_RESOURCE = 'BelVG_AddToCart::add';

    /** @var \Magento\Catalog\Model\ProductRepository $productRepository */
    private $productRepository;

    /** @var \Magento\Framework\Controller\Result\JsonFactory $jsonFactory */
    private $jsonFactory;

    /**
     * Options constructor.
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
     * @param Action\Context $context
     */
    public function __construct(
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\Serialize\Serializer\Json $jsonSerializer,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->productRepository = $productRepository;
        $this->jsonFactory = $jsonFactory;

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
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $request = $this->getRequest();
        if ($productId = $request->getParam('product_id')) {
            if ($product = $this->productRepository->getById($productId)) {
                /** @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable $typedProduct */
                $typedProduct = $product->getTypeInstance();

                $sku = [];
                foreach ($typedProduct->getChildrenIds($productId, false) as $group => $childrens) {
                    foreach ($childrens as $childrenId) {
                        $childProduct = $this->productRepository->getById($childrenId);
                        $sku[] = $childProduct->getSku();
                    }
                }
                $sku = array_unique($sku);

                return $this->jsonFactory->create()->setData([
                    'success' => true,
                    'data' => [
                        'type' => $product->getTypeId(),
                        'product_id' => $productId,
                        'sku' => $sku,
                    ]
                ]);
            }
        }

        return $this->jsonFactory->create()->setData([
            'success' => false
        ]);
    }
}
