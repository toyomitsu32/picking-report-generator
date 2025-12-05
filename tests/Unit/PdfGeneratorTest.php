<?php
/**
 * PDF Generator Unit Tests
 */

declare(strict_types=1);

namespace PickingReport\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PickingReport\Generators\PdfGenerator;
use PickingReport\Models\OrderData;
use PickingReport\Models\Item;
use PickingReport\Models\Part;

class PdfGeneratorTest extends TestCase
{
    private PdfGenerator $generator;
    private string $testOutputDir;

    protected function setUp(): void
    {
        $this->testOutputDir = sys_get_temp_dir() . '/pdf_test_' . uniqid();
        mkdir($this->testOutputDir, 0775, true);
        $this->generator = new PdfGenerator($this->testOutputDir);
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

    public function testRenderHeader(): void
    {
        $order = new OrderData(
            'ORD-001',
            '2024-12-05',
            'Test Customer',
            '2024-12-20'
        );
        
        $html = $this->generator->renderHeader($order);
        
        $this->assertStringContainsString('ピッキング帳票', $html);
        $this->assertStringContainsString('ORD-001', $html);
        $this->assertStringContainsString('Test Customer', $html);
        $this->assertStringContainsString('2024-12-05', $html);
        $this->assertStringContainsString('2024-12-20', $html);
    }

    public function testRenderItemBlock(): void
    {
        $part = new Part('P001', 'Test Part', 2, 36.0, 24.0);
        $item = new Item('I001', 'Test Item', 5, [$part]);
        
        $html = $this->generator->renderItemBlock($item, 1);
        
        $this->assertStringContainsString('Test Item', $html);
        $this->assertStringContainsString('I001', $html);
        $this->assertStringContainsString('数量: 5', $html);
    }

    public function testRenderPartsTable(): void
    {
        $part1 = new Part('P001', 'Part 1', 2, 36.0, 24.0);
        $part2 = new Part('P002', 'Part 2', 3, 48.0, 30.0);
        
        $html = $this->generator->renderPartsTable([$part1, $part2]);
        
        $this->assertStringContainsString('P001', $html);
        $this->assertStringContainsString('Part 1', $html);
        $this->assertStringContainsString('P002', $html);
        $this->assertStringContainsString('Part 2', $html);
        $this->assertStringContainsString('36cm', $html);
        $this->assertStringContainsString('24cm', $html);
    }

    public function testRenderPartsTableWithFormattedDimensions(): void
    {
        $part = new Part('P001', 'Part 1', 2, 36.0, 24.0);
        $part->setSpecification('formatted_width', 'タテ36cm');
        $part->setSpecification('formatted_height', 'ヨコ24cm');
        
        $html = $this->generator->renderPartsTable([$part]);
        
        $this->assertStringContainsString('タテ36cm', $html);
        $this->assertStringContainsString('ヨコ24cm', $html);
    }

    public function testGetOutputDir(): void
    {
        $this->assertEquals($this->testOutputDir, $this->generator->getOutputDir());
    }

    public function testSavePdf(): void
    {
        $content = 'Test PDF content';
        $outputPath = $this->testOutputDir . '/test.pdf';
        
        $result = $this->generator->savePdf($content, $outputPath);
        
        $this->assertTrue($result);
        $this->assertFileExists($outputPath);
        $this->assertEquals($content, file_get_contents($outputPath));
    }
}
