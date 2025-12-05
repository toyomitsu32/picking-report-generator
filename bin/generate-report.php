#!/usr/bin/env php
<?php
/**
 * CLI Script for Report Generation
 * 
 * Usage: php bin/generate-report.php <csv_file_path>
 */

declare(strict_types=1);

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Load Composer autoloader
require BASE_PATH . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->safeLoad();

// Set timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Tokyo');

use PickingReport\Bootstrap;
use PickingReport\Controllers\ReportController;

// Initialize application
Bootstrap::init();
$logger = Bootstrap::getLogger();

// Check arguments
if ($argc < 2) {
    echo "Usage: php bin/generate-report.php <csv_file_path>\n";
    echo "\n";
    echo "Example:\n";
    echo "  php bin/generate-report.php storage/tmp/sample.csv\n";
    exit(1);
}

$csvFilePath = $argv[1];

// Check if file exists
if (!file_exists($csvFilePath)) {
    echo "Error: File not found: {$csvFilePath}\n";
    exit(1);
}

echo "===========================================\n";
echo "  ピッキング帳票生成システム (CLI)\n";
echo "===========================================\n";
echo "\n";
echo "CSVファイル: {$csvFilePath}\n";
echo "処理を開始します...\n";
echo "\n";

// Process the report
$controller = new ReportController();
$config = $controller->getDefaultConfig();
$result = $controller->processReport($csvFilePath, $config);

if ($result['success']) {
    echo "✓ PDF生成成功！\n";
    echo "\n";
    echo "出力ファイル: {$result['pdf_path']}\n";
    echo "受注番号: {$result['order_number']}\n";
    echo "アイテム数: {$result['items_count']}\n";
    echo "総パーツ数: {$result['total_parts']}\n";
    echo "総数量: {$result['total_quantity']}\n";
    echo "\n";
    exit(0);
} else {
    echo "✗ エラーが発生しました\n";
    echo "\n";
    echo "エラー: {$result['error']}\n";
    
    if (isset($result['errors']) && !empty($result['errors'])) {
        echo "\n詳細:\n";
        foreach ($result['errors'] as $error) {
            echo "  - {$error}\n";
        }
    }
    
    echo "\n";
    exit(1);
}
