<?php
/**
 * CSV Parser Unit Tests
 */

declare(strict_types=1);

namespace PickingReport\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PickingReport\Parsers\CsvParser;
use PickingReport\Exceptions\ValidationException;

class CsvParserTest extends TestCase
{
    private CsvParser $parser;

    protected function setUp(): void
    {
        $this->parser = new CsvParser();
    }

    public function testSplitCellByNewline(): void
    {
        $cell = "Line 1\nLine 2\nLine 3";
        $result = $this->parser->splitCellByNewline($cell);
        
        $this->assertCount(3, $result);
        $this->assertEquals('Line 1', $result[0]);
        $this->assertEquals('Line 2', $result[1]);
        $this->assertEquals('Line 3', $result[2]);
    }

    public function testSplitCellByNewlineWithCarriageReturn(): void
    {
        $cell = "Line 1\r\nLine 2\r\nLine 3";
        $result = $this->parser->splitCellByNewline($cell);
        
        $this->assertCount(3, $result);
    }

    public function testSplitCellByNewlineWithEmptyLines(): void
    {
        $cell = "Line 1\n\nLine 2\n";
        $result = $this->parser->splitCellByNewline($cell);
        
        $this->assertCount(2, $result);
        $this->assertEquals('Line 1', $result[0]);
        $this->assertEquals('Line 2', $result[1]);
    }

    public function testSplitCellByComma(): void
    {
        $cell = "Item 1, Item 2, Item 3";
        $result = $this->parser->splitCellByComma($cell);
        
        $this->assertCount(3, $result);
        $this->assertEquals('Item 1', $result[0]);
        $this->assertEquals('Item 2', $result[1]);
        $this->assertEquals('Item 3', $result[2]);
    }

    public function testSplitCellByCommaWithSpaces(): void
    {
        $cell = "Item 1,  Item 2  ,Item 3";
        $result = $this->parser->splitCellByComma($cell);
        
        $this->assertCount(3, $result);
        $this->assertEquals('Item 1', $result[0]);
        $this->assertEquals('Item 2', $result[1]);
    }

    public function testExtractByKeyword(): void
    {
        $cell = "アイテム: 商品A\nパーツ: 部品1\nアイテム: 商品B";
        $result = $this->parser->extractByKeyword($cell, 'アイテム');
        
        $this->assertCount(2, $result);
        $this->assertStringContainsString('商品A', $result[0]);
        $this->assertStringContainsString('商品B', $result[1]);
    }

    public function testExtractByKeywordCaseInsensitive(): void
    {
        $cell = "Item: Product A\nitem: Product B\nITEM: Product C";
        $result = $this->parser->extractByKeyword($cell, 'item');
        
        $this->assertCount(3, $result);
    }

    public function testParseThrowsExceptionForNonExistentFile(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('CSV file not found');
        
        $this->parser->parse('/non/existent/file.csv');
    }

    public function testParseEmptyFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($tempFile, '');
        
        try {
            $this->expectException(ValidationException::class);
            $this->expectExceptionMessage('CSV file is empty');
            
            $this->parser->parse($tempFile);
        } finally {
            unlink($tempFile);
        }
    }
}
