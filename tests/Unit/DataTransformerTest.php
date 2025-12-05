<?php
/**
 * Data Transformer Unit Tests
 */

declare(strict_types=1);

namespace PickingReport\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PickingReport\Transformers\DataTransformer;
use PickingReport\Models\OrderData;
use PickingReport\Models\Item;
use PickingReport\Models\Part;

class DataTransformerTest extends TestCase
{
    private DataTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new DataTransformer();
    }

    public function testApplyDefaultValues(): void
    {
        $data = [
            'field1' => 'value1',
            'field2' => '',
            'field3' => null,
        ];
        
        $defaults = [
            'field2' => '-',
            'field3' => '指定なし',
            'field4' => 'default',
        ];
        
        $result = $this->transformer->applyDefaultValues($data, $defaults);
        
        $this->assertEquals('value1', $result['field1']);
        $this->assertEquals('-', $result['field2']);
        $this->assertEquals('指定なし', $result['field3']);
        $this->assertEquals('default', $result['field4']);
    }

    public function testApplyConditionalDisplayWithShowKeywords(): void
    {
        $data = [
            'option1' => 'あり',
            'option2' => 'なし',
            'option3' => '○',
            'option4' => '×',
        ];
        
        $rules = [
            'option1' => ['show_if' => ['あり', '○']],
            'option2' => ['show_if' => ['あり', '○']],
            'option3' => ['show_if' => ['あり', '○']],
            'option4' => ['show_if' => ['あり', '○']],
        ];
        
        $result = $this->transformer->applyConditionalDisplay($data, $rules);
        
        $this->assertArrayHasKey('option1', $result);
        $this->assertArrayNotHasKey('option2', $result);
        $this->assertArrayHasKey('option3', $result);
        $this->assertArrayNotHasKey('option4', $result);
    }

    public function testApplyConditionalDisplayWithHideKeywords(): void
    {
        $data = [
            'option1' => 'あり',
            'option2' => 'なし',
            'option3' => '通常',
        ];
        
        $rules = [
            'option1' => ['hide_if' => ['なし', '×']],
            'option2' => ['hide_if' => ['なし', '×']],
            'option3' => ['hide_if' => ['なし', '×']],
        ];
        
        $result = $this->transformer->applyConditionalDisplay($data, $rules);
        
        $this->assertArrayHasKey('option1', $result);
        $this->assertArrayNotHasKey('option2', $result);
        $this->assertArrayHasKey('option3', $result);
    }

    public function testFormatNumericValue(): void
    {
        $result1 = $this->transformer->formatNumericValue(36.0, '%.0f');
        $this->assertEquals('36', $result1);
        
        $result2 = $this->transformer->formatNumericValue(36.567, '%.2f');
        $this->assertEquals('36.57', $result2);
        
        $result3 = $this->transformer->formatNumericValue(36.5, '%.1f');
        $this->assertEquals('36.5', $result3);
    }

    public function testAddUnit(): void
    {
        $result1 = $this->transformer->addUnit(36.0, 'cm');
        $this->assertEquals('36cm', $result1);
        
        $result2 = $this->transformer->addUnit(36.0, 'タテ{value}cm');
        $this->assertEquals('タテ36cm', $result2);
        
        $result3 = $this->transformer->addUnit(5.5, 'kg');
        $this->assertEquals('5.5kg', $result3);
    }

    public function testApplyNumericConversionRules(): void
    {
        $data = [
            'width' => '36.0',
            'height' => '24.567',
            'weight' => '5.5',
        ];
        
        $rules = [
            'width' => ['format' => '%.0f', 'unit' => 'cm'],
            'height' => ['decimals' => 2],
            'weight' => ['unit' => 'kg'],
        ];
        
        $result = $this->transformer->applyNumericConversionRules($data, $rules);
        
        $this->assertEquals('36cm', $result['width']);
        $this->assertEquals(24.57, $result['height']);
        $this->assertEquals('5.5kg', $result['weight']);
    }

    public function testTransformOrderData(): void
    {
        $part = new Part('P001', 'Part 1', 2, 36.0, 24.0);
        $item = new Item('I001', 'Item 1', 5, [$part]);
        $order = new OrderData('ORD001', '2024-12-05', 'Customer', '2024-12-20', [$item]);
        
        $config = [
            'dimension_format' => '%.0f',
            'dimension_unit' => 'cm',
        ];
        
        $result = $this->transformer->transform($order, $config);
        
        $this->assertInstanceOf(OrderData::class, $result);
        $this->assertCount(1, $result->getItems());
        
        $transformedItem = $result->getItems()[0];
        $this->assertCount(1, $transformedItem->getParts());
        
        $transformedPart = $transformedItem->getParts()[0];
        $this->assertEquals('36cm', $transformedPart->getSpecification('formatted_width'));
        $this->assertEquals('24cm', $transformedPart->getSpecification('formatted_height'));
    }
}
