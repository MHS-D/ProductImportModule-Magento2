<?php

namespace Vendor\ProductImport\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ProductImport extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('product_import_files', 'id');
    }
}
