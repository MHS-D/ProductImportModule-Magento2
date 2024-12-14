<?php

namespace Vendor\ProductImport\Model\ResourceModel\Import;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Vendor\ProductImport\Model\ProductImport;
use Vendor\ProductImport\Model\ResourceModel\ProductImport as ProductImportResource;

class Collection extends AbstractCollection
{
    protected $_entityClass = ProductImport::class;
    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'product_import_files_collection';

    protected function _construct()
    {
        $this->_init(
            ProductImport::class,
            ProductImportResource::class
        );
    }
}
