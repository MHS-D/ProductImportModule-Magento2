<?php

namespace Vendor\ProductImport\Model;

use Magento\Framework\ObjectManagerInterface;

class ProductImportFactory
{
    protected $objectManager;

    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * Create the ProductImport model.
     * @return \Vendor\ProductImport\Model\ProductImport
     */
    public function create()
    {
        return $this->objectManager->create(ProductImport::class);
    }
}
