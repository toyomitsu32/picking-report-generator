<?php
/**
 * Calculation Engine Unit Tests
 */

declare(strict_types=1);

namespace PickingReport\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PickingReport\Calculators\CalculationEngine;
use PickingReport\Models\OrderData;
use PickingReport\Models\Item;
use PickingReport\Models\Part;
use PickingReport\Exceptions\ValidationException;

class CalculationEngineTest extends TestCase
{
    private CalculationEngine $calculator;

    protected function setUp(): void
    {
        $this->calculator = new CalculationEngine();
    }

    public function testCalculateTotalParts(): void
    {
        $part1 = new Part('P001', 'Part 1', 2);
        $part2 = new Part('P002', 'Part 2', 3);
        $item1 = new Item('I001', 'Item 1', 5, [$part1, $part2]);
        
        $part3 = new Part('P003', 'Part 3', 1);
        $item2 = new Item('I002', 'Item 2', 3, [$part3]);
        
        $total = $this->calculator->calculateTotalParts([$item1, $item2]);
        
        $this->assertEquals(3, $total); // 2 parts in item1 + 1 part in item2
    }

    public function testCalculateTotalQuantity(): void
    {
        $item1 = new Item('I001', 'Item 1', 5);
        $item2 = new Item('I002', 'Item 2', 3);
        $item3 = new Item('I003', 'Item 3', 7);
        
        $total = $this->calculator->calculateTotalQuantity([$item1, $item2, $item3]);
        
        $this->assertEquals(15.0, $total);
    }

    public function testConvertSize(): void
    {
        // cm to m
        $result1 = $this->calculator->convertSize(100, 'cm', 'm');
        $this->assertEquals(1.0, $result1);
        
        // m to cm
        $result2 = $this->calculator->convertSize(1.5, 'm', 'cm');
        $this->assertEquals(150.0, $result2);
        
        // mm to cm
        $result3 = $this->calculator->convertSize(50, 'mm', 'cm');
        $this->assertEquals(5.0, $result3);
    }

    public function testConvertSizeWithInvalidUnit(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unknown source unit');
        
        $this->calculator->convertSize(100, 'invalid', 'm');
    }

    public function testApplyConditionalCalculation(): void
    {
        $data = ['quantity' => 15];
        
        $result = $this->calculator->applyConditionalCalculation(
            $data,
            'quantity > 10',
            fn($d) => $d['quantity'] * 2
        );
        
        $this->assertEquals(30.0, $result);
    }

    public function testApplyConditionalCalculationNotMet(): void
    {
        $data = ['quantity' => 5];
        
        $result = $this->calculator->applyConditionalCalculation(
            $data,
            'quantity > 10',
            fn($d) => $d['quantity'] * 2
        );
        
        $this->assertEquals(0.0, $result);
    }

    public function testCalculateTotalPartsWithQuantities(): void
    {
        $part1 = new Part('P001', 'Part 1', 2); // 2 parts
        $part2 = new Part('P002', 'Part 2', 3); // 3 parts
        $item1 = new Item('I001', 'Item 1', 5, [$part1, $part2]); // quantity 5
        
        $part3 = new Part('P003', 'Part 3', 4); // 4 parts
        $item2 = new Item('I002', 'Item 2', 2, [$part3]); // quantity 2
        
        $total = $this->calculator->calculateTotalPartsWithQuantities([$item1, $item2]);
        
        // (2 + 3) * 5 + 4 * 2 = 25 + 8 = 33
        $this->assertEquals(33, $total);
    }

    public function testCalculateTotalArea(): void
    {
        $part1 = new Part('P001', 'Part 1', 2, 10.0, 5.0); // 10 x 5 x 2 = 100
        $part2 = new Part('P002', 'Part 2', 1, 20.0, 10.0); // 20 x 10 x 1 = 200
        $item = new Item('I001', 'Item 1', 1, [$part1, $part2]);
        
        $totalArea = $this->calculator->calculateTotalArea([$item]);
        
        $this->assertEquals(300.0, $totalArea);
    }

    public function testCalculateTotalAreaWithNullDimensions(): void
    {
        $part1 = new Part('P001', 'Part 1', 2, 10.0, 5.0);
        $part2 = new Part('P002', 'Part 2', 1, null, null); // No dimensions
        $item = new Item('I001', 'Item 1', 1, [$part1, $part2]);
        
        $totalArea = $this->calculator->calculateTotalArea([$item]);
        
        $this->assertEquals(100.0, $totalArea); // Only part1 counted
    }

    public function testCalculateWithOrderData(): void
    {
        $part = new Part('P001', 'Part 1', 2, 10.0, 5.0);
        $item = new Item('I001', 'Item 1', 3, [$part]);
        $order = new OrderData('ORD001', '2024-12-05', 'Customer', '2024-12-20', [$item]);
        
        $result = $this->calculator->calculate($order);
        
        $this->assertEquals(1, $result->getMetadataValue('total_parts'));
        $this->assertEquals(3.0, $result->getMetadataValue('total_quantity'));
        $this->assertEquals(6, $result->getMetadataValue('total_parts_with_quantities')); // 2 * 3
        $this->assertEquals(100.0, $result->getMetadataValue('total_area')); // 10 * 5 * 2
    }

    public function testValidateCalculationResultThrowsExceptionForOutOfBounds(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('out of reasonable bounds');
        
        // Create items with extremely large quantities to trigger validation
        $items = [];
        for ($i = 0; $i < 100000; $i++) {
            $items[] = new Item("I{$i}", "Item {$i}", 100000);
        }
        
        $this->calculator->calculateTotalQuantity($items);
    }
}
