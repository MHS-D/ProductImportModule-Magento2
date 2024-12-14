<?php

namespace Vendor\ProductImport\Controller\Adminhtml\Import;

use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\File\Csv;
use Magento\Framework\Controller\ResultFactory;
use Vendor\ProductImport\Model\ProductImportFactory;

class Upload extends Action
{
    protected $csvProcessor;
    protected $directoryList;
    protected $productImportFactory;

    public function __construct(
        Action\Context $context,
        Csv $csvProcessor,
        DirectoryList $directoryList,
        ProductImportFactory $productImportFactory
    ) {
        parent::__construct($context);
        $this->csvProcessor = $csvProcessor;
        $this->directoryList = $directoryList;
        $this->productImportFactory = $productImportFactory;
    }

    public function execute(){

        if (!$this->getRequest()->isPost()) {
            $this->messageManager->addErrorMessage(__('No file uploaded.'));
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
                ->setUrl($this->_redirect->getRefererUrl());
        }

        $files = $this->getRequest()->getFiles();

        // Errors and Extension validation
        if (($files['products_csv']['error'] !== 0 || !$this->isCsvFile($files['products_csv'])) &&
            ($files['categories_csv']['error'] !== 0 || !$this->isCsvFile($files['categories_csv']))) {
            $this->messageManager->addErrorMessage(__('Invalid file type. Only .csv files are allowed.'));
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
                ->setUrl($this->_redirect->getRefererUrl());
        }

        // Handle Products CSV upload
        if (isset($files['products_csv']) && $files['products_csv']['error'] === 0) {
            $savedPath = $this->saveUploadedFile($files['products_csv'], 'products_' . time() . '.csv');
            $this->processProductsCSV($savedPath);
        }

        // Handle Categories CSV upload
        if (isset($files['categories_csv']) && $files['categories_csv']['error'] === 0) {
            $savedPath = $this->saveUploadedFile($files['categories_csv'], 'categories_' . time() . '.csv');
            $this->processCategoriesCSV($savedPath);
        }

        $this->messageManager->addSuccessMessage(__('CSV files processed successfully, check statics table.'));

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
            ->setUrl($this->_redirect->getRefererUrl());
    }


    protected function processProductsCSV($filePath)
    {
        $this->processCSV($filePath, 'products', [$this, 'validateProductRow']);
    }

    protected function processCategoriesCSV($filePath)
    {
        $this->processCSV($filePath, 'categories', [$this, 'validateCategoryRow']);
    }

    protected function processCSV($filePath, $type, callable $validationCallback)
    {
        $csvData = $this->csvProcessor->getData($filePath);
        $headers = array_shift($csvData);

        $validRows = [];
        $invalidRows = [];
        $errorSummary = '';

        foreach ($csvData as $index => $row) {
            $rowIndex = $index + 2;
            $validationResult = call_user_func($validationCallback, $row, $rowIndex);

            if ($validationResult['isValid']) {
                $validRows[] = $row;
            } else {
                $invalidRows[] = $row;
                $errorSummary .= $validationResult['errors'];
            }
        }

        $this->saveImportResult($filePath, $validRows, $invalidRows, $errorSummary, $type);
    }

    protected function validateProductRow($row, $rowIndex)
    {
        $errors = '';
        $isValid = true;

        // SKU Validation
        if (empty($row[0]) || !ctype_alnum($row[0])) {
            $isValid = false;
            $errors .= "Row $rowIndex: Invalid SKU (must be alphanumeric)\n";
        }

        // Required fields
        if (empty($row[1]) || empty($row[2]) || empty($row[3]) || empty($row[4]) || empty($row[5])) {
            $isValid = false;
            $errors .= "Row $rowIndex: Missing required fields\n";
        }

        // URL Key Validation
        if (!empty($row[5]) && !filter_var($row[5], FILTER_VALIDATE_URL)) {
            $isValid = false;
            $errors .= "Row $rowIndex: Invalid URL key (must be a valid URL)\n";
        }

        // Price Validation
        if (!isset($row[6]) || !is_numeric($row[6])) {
            $isValid = false;
            $errors .= "Row $rowIndex: Invalid price (must be numeric)\n";
        }

        return ['isValid' => $isValid, 'errors' => $errors];
    }

    protected function validateCategoryRow($row, $rowIndex)
    {
        $errors = '';
        $isValid = true;

        // SKU Validation
        if (empty($row[0]) || !ctype_alnum($row[0])) {
            $isValid = false;
            $errors .= "Row $rowIndex: Invalid SKU (must be alphanumeric)\n";
        }

        // Name Validation
        if (empty($row[1])) {
            $isValid = false;
            $errors .= "Row $rowIndex: Missing category name\n";
        }

        // URL Key Validation
        if (!empty($row[2]) && !filter_var($row[2], FILTER_VALIDATE_URL)) {
            $isValid = false;
            $errors .= "Row $rowIndex: Invalid URL key (must be a valid URL)\n";
        }

        return ['isValid' => $isValid, 'errors' => $errors];
    }

    protected function saveImportResult($filePath, $validRows, $invalidRows, $errorSummary, $type)
    {
        $productImport = $this->productImportFactory->create();
        $productImport->setFileName(basename($filePath))
            ->setUploadDate(date('Y-m-d H:i:s'))
            ->setValidationStatus(count($invalidRows) > 0 ? 'failed' : 'success')
            ->setErrorSummary($errorSummary)
            ->setProcessedLines(count($validRows) + count($invalidRows))
            ->setErrorLines(count($invalidRows))
            ->setType($type)
            ->save();
    }

    protected function saveUploadedFile($uploadedFile, $fileName)
    {
        $varDirectory = $this->directoryList->getPath(DirectoryList::VAR_DIR);
        $uploadDirectory = $varDirectory . '/import_csv'; // Path: var/import_csv

        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0775, true); // Create directory if it doesn't exist
        }

        $destination = $uploadDirectory . '/' . $fileName;
        move_uploaded_file($uploadedFile['tmp_name'], $destination);

        return $destination;
    }

    protected function isCsvFile($file)
    {
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        return strtolower($fileExtension) === 'csv';
    }


}
