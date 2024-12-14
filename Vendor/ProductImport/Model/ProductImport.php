<?php

namespace Vendor\ProductImport\Model;

use Magento\Framework\Model\AbstractModel;

class ProductImport extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Vendor\ProductImport\Model\ResourceModel\ProductImport::class);
    }
}
