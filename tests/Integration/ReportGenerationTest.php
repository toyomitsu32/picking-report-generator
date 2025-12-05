<?php
/**
 * Report Generation Integration Tests
 * 
 * Tests the complete CSV to PDF workflow.
 */

declare(strict_types=1);

namespace PickingReport\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PickingReport\Controllers\ReportController;

class ReportGenerationTest extends TestCase
{
    private ReportController $controller;
    private string $testOutputDir;

    protected function setUp(): void
    {
        $this->testOutputDir = sys_get_temp_dir() . '/pdf_integration_test_' . uniqid();
        mkdir($this->testOutputDir, 0775, true);
        
        $this->controller = new ReportController();
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (is_dir($this->testOutputDir)) {
            $files = glob($this->testOutputDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testOutputDir);
        }
    }

    public function testCompleteWorkflowWithSampleCsv(): void
    {
        // Create a sample CSV file
        $csvContent = <<<CSV
受注番号:,ORD-2024-001
受注日:,2024-12-05
顧客名:,株式会社テスト
納期:,2024-12-20

アイテム:,商品A,数量: 10
パーツ:,部品A1,タテ36cm,数量: 2
パーツ:,部品A2,タテ48cm,数量: 1

アイテム:,商品B,数量: 5
パーツ:,部品B1,タテ30cm,数量: 3
CSV;

        $csvPath = $this->testOutputDir . '/test.csv';
        file_put_contents($csvPath, $csvContent);
        
        // Process the report
        $config = $this->controller->getDefaultConfig();
        $result = $this->controller->processReport($csvPath, $config);
        
        // Verify success
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('pdf_path', $result);
        $this->assertFileExists($result['pdf_path']);
        
        // Verify metadata
        $this->assertEquals('ORD-2024-001', $result['order_number']);
        $this->assertEquals(2, $result['items_count']);
        $this->assertGreaterThan(0, filesize($result['pdf_path']));
        
        // Clean up generated PDF
        if (file_exists($result['pdf_path'])) {
            unlink($result['pdf_path']);
        }
    }

    public function testWorkflowWithInvalidCsv(): void
    {
        // Create an empty CSV file
        $csvPath = $this->testOutputDir . '/empty.csv';
        file_put_contents($csvPath, '');
        
        // Process the report
        $result = $this->controller->processReport($csvPath);
        
        // Verify failure
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('validation', $result['type']);
    }

    public function testWorkflowWithNonExistentFile(): void
    {
        $result = $this->controller->processReport('/non/existent/file.csv');
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testGetDefaultConfig(): void
    {
        $config = $this->controller->getDefaultConfig();
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('dimension_format', $config);
        $this->assertArrayHasKey('dimension_unit', $config);
        $this->assertEquals('%.0f', $config['dimension_format']);
        $this->assertEquals('cm', $config['dimension_unit']);
    }
}
