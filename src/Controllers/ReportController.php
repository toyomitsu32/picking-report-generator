<?php
/**
 * Report Controller
 * 
 * Main controller for CSV to PDF conversion workflow.
 */

declare(strict_types=1);

namespace PickingReport\Controllers;

use PickingReport\Parsers\CsvParser;
use PickingReport\Transformers\DataTransformer;
use PickingReport\Calculators\CalculationEngine;
use PickingReport\Generators\PdfGenerator;
use PickingReport\Exceptions\ValidationException;
use PickingReport\Exceptions\PdfGenerationException;
use PickingReport\Bootstrap;

class ReportController
{
    private CsvParser $csvParser;
    private DataTransformer $dataTransformer;
    private CalculationEngine $calculationEngine;
    private PdfGenerator $pdfGenerator;

    public function __construct(
        ?CsvParser $csvParser = null,
        ?DataTransformer $dataTransformer = null,
        ?CalculationEngine $calculationEngine = null,
        ?PdfGenerator $pdfGenerator = null
    ) {
        $this->csvParser = $csvParser ?? new CsvParser();
        $this->dataTransformer = $dataTransformer ?? new DataTransformer();
        $this->calculationEngine = $calculationEngine ?? new CalculationEngine();
        $this->pdfGenerator = $pdfGenerator ?? new PdfGenerator();
    }

    /**
     * Process CSV file and generate PDF
     * 
     * @param string $csvFilePath Path to CSV file
     * @param array $config Optional configuration for transformation
     * @return array Result with PDF path and metadata
     */
    public function processReport(string $csvFilePath, array $config = []): array
    {
        $logger = Bootstrap::getLogger();
        
        try {
            $logger->info('Starting report processing', ['file' => $csvFilePath]);
            
            // Step 1: Parse CSV
            $logger->debug('Parsing CSV file');
            $parsedData = $this->csvParser->parse($csvFilePath);
            $orderData = $parsedData->getOrder();
            $logger->info('CSV parsed successfully', [
                'order_number' => $orderData->getOrderNumber(),
                'items_count' => $orderData->getItemsCount()
            ]);
            
            // Step 2: Transform data
            $logger->debug('Transforming data');
            $transformedData = $this->dataTransformer->transform($orderData, $config);
            $logger->info('Data transformed successfully');
            
            // Step 3: Calculate totals
            $logger->debug('Calculating totals');
            $calculatedData = $this->calculationEngine->calculate($transformedData);
            $logger->info('Calculations completed', [
                'total_parts' => $calculatedData->getMetadataValue('total_parts'),
                'total_quantity' => $calculatedData->getMetadataValue('total_quantity')
            ]);
            
            // Step 4: Generate PDF
            $logger->debug('Generating PDF');
            $pdfPath = $this->pdfGenerator->generate($calculatedData);
            $logger->info('PDF generated successfully', ['path' => $pdfPath]);
            
            return [
                'success' => true,
                'pdf_path' => $pdfPath,
                'order_number' => $calculatedData->getOrderNumber(),
                'items_count' => $calculatedData->getItemsCount(),
                'total_parts' => $calculatedData->getMetadataValue('total_parts'),
                'total_quantity' => $calculatedData->getMetadataValue('total_quantity'),
            ];
            
        } catch (ValidationException $e) {
            $logger->warning('Validation error during report processing', [
                'error' => $e->getMessage(),
                'errors' => $e->getErrors()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'errors' => $e->getErrors(),
                'type' => 'validation'
            ];
            
        } catch (PdfGenerationException $e) {
            $logger->error('PDF generation error', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'PDFの生成に失敗しました: ' . $e->getMessage(),
                'type' => 'pdf_generation'
            ];
            
        } catch (\Exception $e) {
            $logger->critical('Unexpected error during report processing', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'システムエラーが発生しました。管理者に連絡してください。',
                'type' => 'system'
            ];
        }
    }

    /**
     * Process CSV file from upload
     * 
     * @param array $uploadedFile $_FILES array element
     * @param array $config Optional configuration
     * @return array Result with PDF path and metadata
     */
    public function processUploadedFile(array $uploadedFile, array $config = []): array
    {
        $logger = Bootstrap::getLogger();
        
        // Validate upload
        if (!isset($uploadedFile['tmp_name']) || !is_uploaded_file($uploadedFile['tmp_name'])) {
            $logger->warning('Invalid file upload');
            return [
                'success' => false,
                'error' => 'ファイルのアップロードに失敗しました',
                'type' => 'upload'
            ];
        }
        
        // Validate file type
        $allowedTypes = ['text/csv', 'application/csv', 'text/plain'];
        $fileType = $uploadedFile['type'] ?? '';
        
        if (!in_array($fileType, $allowedTypes)) {
            // Also check file extension
            $fileName = $uploadedFile['name'] ?? '';
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            if ($extension !== 'csv') {
                $logger->warning('Invalid file type', ['type' => $fileType, 'extension' => $extension]);
                return [
                    'success' => false,
                    'error' => 'CSVファイルのみアップロード可能です',
                    'type' => 'validation'
                ];
            }
        }
        
        // Validate file size
        $maxSize = (int)($_ENV['UPLOAD_MAX_SIZE'] ?? 10485760); // 10MB default
        if ($uploadedFile['size'] > $maxSize) {
            $logger->warning('File size exceeds limit', ['size' => $uploadedFile['size'], 'max' => $maxSize]);
            return [
                'success' => false,
                'error' => 'ファイルサイズが上限を超えています',
                'type' => 'validation'
            ];
        }
        
        // Process the uploaded file
        return $this->processReport($uploadedFile['tmp_name'], $config);
    }

    /**
     * Get default transformation configuration
     */
    public function getDefaultConfig(): array
    {
        return [
            // 数値フォーマット設定（要件5.1, 5.2）
            'dimension_format' => '%.1f',  // 小数点1桁
            'dimension_unit' => 'cm',
            
            // 条件分岐表示ルール（要件4.1, 4.2）
            'item_display_rules' => [
                'shipping_method' => [
                    'show_if' => ['あり', '○', 'yes', 'Yes'],
                    'hide_if' => ['なし', '×', 'no', 'No'],
                ],
                'packaging' => [
                    'show_if' => ['あり', '○', 'yes', 'Yes'],
                    'hide_if' => ['なし', '×', 'no', 'No'],
                ],
                'insurance' => [
                    'show_if' => ['あり', '○', 'yes', 'Yes'],
                    'hide_if' => ['なし', '×', 'no', 'No'],
                ],
                'tracking' => [
                    'show_if' => ['あり', '○', 'yes', 'Yes'],
                    'hide_if' => ['なし', '×', 'no', 'No'],
                ],
            ],
            
            // デフォルト値設定（要件4.3, 5.3）
            'item_defaults' => [
                'status' => '未処理',
                'notes' => '-',
                '担当者' => '指定なし',
            ],
            'part_defaults' => [
                'notes' => '-',
                'width' => 0,
                'height' => 0,
                'quantity' => 0,
                'weight' => '-',
            ],
            'metadata_defaults' => [
                'remarks' => '-',
                '担当者' => '指定なし',
                '特記事項' => 'なし',
            ],
            
            // 数値変換ルール（要件5.1, 5.2, 5.4）
            'numeric_conversion_rules' => [
                'width' => [
                    'format' => '%.1f',
                    'unit' => 'cm',
                    'decimals' => 1,
                ],
                'height' => [
                    'format' => '%.1f',
                    'unit' => 'cm',
                    'decimals' => 1,
                ],
                'weight' => [
                    'format' => '%.2f',
                    'unit' => 'kg',
                    'decimals' => 2,
                ],
                'price' => [
                    'format' => '%.0f',
                    'unit' => '円',
                    'decimals' => 0,
                ],
            ],
        ];
    }
}
