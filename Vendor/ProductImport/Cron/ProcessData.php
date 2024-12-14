<?php

namespace Vendor\ProductImport\Cron;

use Vendor\ProductImport\Model\ProductImportFactory;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

class ProcessData
{
    protected $productImportFactory;
    protected $categoryRepository;
    protected $categoryFactory;
    protected $productRepository;
    protected $productFactory;
    protected $logger;
    protected $categoryCollectionFactory;
    protected $productCollectionFactory;

    public function __construct(
        ProductImportFactory $productImportFactory,
        CategoryRepositoryInterface $categoryRepository,
        CategoryFactory $categoryFactory,
        ProductRepositoryInterface $productRepository,
        ProductFactory $productFactory,
        LoggerInterface $logger,
        CategoryCollectionFactory $categoryCollectionFactory,
        ProductCollectionFactory $productCollectionFactory
    ) {
        $this->productImportFactory = $productImportFactory;
        $this->categoryRepository = $categoryRepository;
        $this->categoryFactory = $categoryFactory;
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->logger = $logger;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    public function execute()
    {
        try {
            // Using collection factory to get the product import data
            $collection = $this->productImportFactory->create()->getCollection()
                ->addFieldToFilter('validation_status', 'success')
                ->addFieldToFilter('cronjob_status', 'not started');

            foreach ($collection as $item) {
                try {
                    if ($item->getType() === 'categories') {
                        $this->processCategory($item);
                    } elseif ($item->getType() === 'products') {
                        $this->processProduct($item);
                    }

                    // $productImportResource = $this->productImportFactory->create()->getResource();
                    // $item->setCronjobStatus('success')->save();
                    // $productImportResource->save($item);  //not working IDK why XD

                   $this->handleCronStatus($item, 'success');
                } catch (\Exception $e) {
                    $this->logger->error('Error processing item ID ' . $item->getId() . ': ' . $e->getMessage());
                    $this->handleCronStatus($item, 'failed','CronJob Error: ' . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error processing import data: ' . $e->getMessage());
        }
    }

    protected function handleCronStatus($model, $status, $error = null)
    {
        $connection = $this->productImportFactory->create()->getResource()->getConnection();
        $connection->update(
            $connection->getTableName('product_import_files'),
            ['cronjob_status' => $status, 'error_summary' => $error],
            ['id = ?' => $model->getId()]
        );
    }



    protected function processCategory($item)
    {
        try {
            $data = $this->csvToArray($item->getFileName());

            foreach ($data as $categoryData) {
                try {
                    $categoryCollection = $this->categoryCollectionFactory->create();
                    $categoryCollection->addFieldToFilter('url_key', $categoryData['url key']);
                    $category = $categoryCollection->getFirstItem();

                    if ($category->getId()) {
                        $category->setName($categoryData['name']);
                        $this->categoryRepository->save($category);
                    } else {
                        $newCategory = $this->categoryFactory->create();
                        $newCategory->setName($categoryData['name'])
                            ->setUrlKey($categoryData['url key'])
                            ->setIsActive(true);
                        $this->categoryRepository->save($newCategory);
                        $this->logger->info('Category updated: ' . $categoryData['name']);
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Error processing category with URL key ' . $categoryData['url key'] . ': ' . $e->getMessage());
                    throw new \Exception($e->getMessage());
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Category processing failed: ' . $e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }

    protected function processProduct($item)
    {
        try {
            $data = $this->csvToArray($item->getFileName());
            foreach ($data as $productData) {
                try {

                    $productCollection = $this->productCollectionFactory->create();
                    $productCollection->addFieldToFilter('sku', $productData['sku']);
                    $product = $productCollection->getFirstItem();


                    if ($product->getId()) {
                        // Update existing product
                        $product->setName($productData['name']);
                        $product->setPrice($productData['price']);

                        // Add more fields as needed
                        if (!empty($productData['short discription'])) {
                            $product->setShortDescription($productData['short discription']);
                        }
                        if (!empty($productData['long discription'])) {
                            $product->setDescription($productData['long discription']);
                        }

                        $this->productRepository->save($product);
                        $this->logger->info('Product updated: ' . $productData['sku']);
                    } else {
                        // Create new product if not found
                        $newProduct = $this->productFactory->create();
                        $newProduct->setSku($productData['sku'])
                            ->setName($productData['name'])
                            ->setPrice($productData['price'])
                            ->setAttributeSetId(4) // Default attribute set
                            ->setVisibility(4)
                            ->setStatus(1)
                            ->setShortDescription($productData['short discription'] ?? '')
                            ->setDescription($productData['long discription'] ?? '');

                        $this->productRepository->save($newProduct);
                        $this->logger->info('Product created: ' . $productData['sku']);
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Error processing product SKU ' . $productData['sku'] . ': ' . $e->getMessage());
                    throw new \Exception($e->getMessage());
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Product processing failed: ' . $e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }

    protected function csvToArray($filePath)
    {
        try{
            $filePath = BP . '/var/import_csv/' . $filePath;
            if (!file_exists($filePath)) {
                $this->logger->error('File not found: ' . $filePath);
                return [];
            }

            $csvData = array_map('str_getcsv', file($filePath)); // Read CSV data
            $headers = array_shift($csvData); // Extract headers

            $this->logger->info('CSV Headers: ' . implode(',', $headers));

            return array_map(function ($row) use ($headers) {
                return array_combine($headers, $row);
            }, $csvData);
        }catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
