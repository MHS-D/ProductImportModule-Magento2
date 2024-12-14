<?php

namespace Vendor\ProductImport\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (!$installer->tableExists('product_import_files')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('product_import_files')
            )
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'ID'
            )
            ->addColumn(
                'file_name',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'File Name'
            )
            ->addColumn(
                'type',
                Table::TYPE_TEXT,
                50,
                ['nullable' => false],
                'Type'
            )
            ->addColumn(
                'upload_date',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => false],
                'Upload Date'
            )
            ->addColumn(
                'validation_status',
                Table::TYPE_TEXT,
                50,
                ['nullable' => false],
                'Validation Status'
            )
            ->addColumn(
                'error_summary',
                Table::TYPE_TEXT,
                null,
                ['nullable' => true],
                'Error Summary'
            )
            ->addColumn(
                'processed_lines',
                Table::TYPE_INTEGER,
                null,
                ['default' => 0],
                'Processed Lines'
            )
            ->addColumn(
                'error_lines',
                Table::TYPE_INTEGER,
                null,
                ['default' => 0],
                'Error Lines'
            )
            ->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Created At'
            )
            ->addColumn(
                'updated_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                'Updated At'
            )
            ->addColumn(
                'cronjob_status',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false, 'default' => 'not started'],
                'Cron Job Status'
            );


            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
}
