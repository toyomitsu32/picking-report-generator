<?php
/**
 * Calculation Engine
 * 
 * Performs calculations on order data.
 */

declare(strict_types=1);

namespace PickingReport\Calculators;

use PickingReport\Models\OrderData;
use PickingReport\Models\Item;
use PickingReport\Exceptions\ValidationException;

class CalculationEngine
{
    private const MAX_REASONABLE_VALUE = 1000000;
    private const MIN_REASONABLE_VALUE = -1000000;

    /**
     * Calculate total number of parts across all items
     * 
     * @param Item[] $items Array of items
     * @return int Total parts count
     */
    public function calculateTotalParts(array $items): int
    {
        $total = 0;
        
        foreach ($items as $item) {
            $total += $item->getPartsCount();
        }
        
        $this->validateCalculationResult($total, 'Total parts count');
        
        return $total;
    }

    /**
     * Calculate total quantity across all items
     * 
     * @param Item[] $items Array of items
     * @return float Total quantity
     */
    public function calculateTotalQuantity(array $items): float
    {
        $total = 0.0;
        
        foreach ($items as $item) {
            $total += $item->getQuantity();
        }
        
        $this->validateCalculationResult($total, 'Total quantity');
        
        return $total;
    }

    /**
     * Convert size from one unit to another
     * 
     * @param float $value Value to convert
     * @param string $fromUnit Source unit (e.g., 'cm', 'm', 'mm')
     * @param string $toUnit Target unit
     * @return float Converted value
     */
    public function convertSize(float $value, string $fromUnit, string $toUnit): float
    {
        // Define conversion factors to base unit (meters)
        $toMeters = [
            'mm' => 0.001,
            'cm' => 0.01,
            'm' => 1.0,
            'km' => 1000.0,
        ];
        
        if (!isset($toMeters[$fromUnit])) {
            throw new ValidationException("Unknown source unit: {$fromUnit}");
        }
        
        if (!isset($toMeters[$toUnit])) {
            throw new ValidationException("Unknown target unit: {$toUnit}");
        }
        
        // Convert to meters, then to target unit
        $valueInMeters = $value * $toMeters[$fromUnit];
        $result = $valueInMeters / $toMeters[$toUnit];
        
        $this->validateCalculationResult($result, "Size conversion from {$fromUnit} to {$toUnit}");
        
        return $result;
    }

    /**
     * Apply conditional calculation based on condition
     * 
     * @param array $data Data to evaluate
     * @param string $condition Condition to check (e.g., 'field > 10')
     * @param callable $formula Calculation formula to apply if condition is true
     * @return float Calculation result
     */
    public function applyConditionalCalculation(array $data, string $condition, callable $formula): float
    {
        // Evaluate condition
        $conditionMet = $this->evaluateCondition($data, $condition);
        
        if (!$conditionMet) {
            return 0.0;
        }
        
        // Apply formula
        $result = $formula($data);
        
        $this->validateCalculationResult($result, 'Conditional calculation');
        
        return $result;
    }

    /**
     * Calculate total parts with quantities
     * 
     * @param Item[] $items Array of items
     * @return int Total parts considering their quantities
     */
    public function calculateTotalPartsWithQuantities(array $items): int
    {
        $total = 0;
        
        foreach ($items as $item) {
            foreach ($item->getParts() as $part) {
                $total += $part->getQuantity() * $item->getQuantity();
            }
        }
        
        $this->validateCalculationResult($total, 'Total parts with quantities');
        
        return $total;
    }

    /**
     * Calculate weighted total based on item quantities
     * 
     * @param Item[] $items Array of items
     * @param string $field Field to sum (from part specifications)
     * @return float Weighted total
     */
    public function calculateWeightedTotal(array $items, string $field): float
    {
        $total = 0.0;
        
        foreach ($items as $item) {
            foreach ($item->getParts() as $part) {
                $value = $part->getSpecification($field);
                if ($value !== null && is_numeric($value)) {
                    $total += (float)$value * $part->getQuantity();
                }
            }
        }
        
        $this->validateCalculationResult($total, "Weighted total for field: {$field}");
        
        return $total;
    }

    /**
     * Calculate area (width × height) for parts
     * 
     * @param Item[] $items Array of items
     * @return float Total area in square units
     */
    public function calculateTotalArea(array $items): float
    {
        $totalArea = 0.0;
        
        foreach ($items as $item) {
            foreach ($item->getParts() as $part) {
                $width = $part->getWidth();
                $height = $part->getHeight();
                
                if ($width !== null && $height !== null) {
                    $area = $width * $height * $part->getQuantity();
                    $totalArea += $area;
                }
            }
        }
        
        $this->validateCalculationResult($totalArea, 'Total area');
        
        return $totalArea;
    }

    /**
     * Apply calculation to OrderData and store results in metadata
     * 
     * @param OrderData $orderData Order data to calculate
     * @return OrderData Order data with calculation results
     */
    public function calculate(OrderData $orderData): OrderData
    {
        $items = $orderData->getItems();
        
        // Calculate totals
        $totalParts = $this->calculateTotalParts($items);
        $totalQuantity = $this->calculateTotalQuantity($items);
        $totalPartsWithQty = $this->calculateTotalPartsWithQuantities($items);
        $totalArea = $this->calculateTotalArea($items);
        
        // Store in metadata
        $orderData->setMetadataValue('total_parts', $totalParts);
        $orderData->setMetadataValue('total_quantity', $totalQuantity);
        $orderData->setMetadataValue('total_parts_with_quantities', $totalPartsWithQty);
        $orderData->setMetadataValue('total_area', $totalArea);
        
        return $orderData;
    }

    /**
     * Validate calculation result is within reasonable bounds
     * 
     * @throws ValidationException if result is out of bounds
     */
    private function validateCalculationResult(float|int $result, string $calculationType): void
    {
        if ($result < self::MIN_REASONABLE_VALUE || $result > self::MAX_REASONABLE_VALUE) {
            throw new ValidationException(
                "Calculation result out of reasonable bounds for {$calculationType}: {$result}"
            );
        }
        
        if (is_float($result) && (is_nan($result) || is_infinite($result))) {
            throw new ValidationException(
                "Invalid calculation result for {$calculationType}: " . 
                (is_nan($result) ? 'NaN' : 'Infinite')
            );
        }
    }

    /**
     * Evaluate a simple condition string
     * 
     * @param array $data Data to evaluate against
     * @param string $condition Condition string (e.g., 'quantity > 10')
     * @return bool Whether condition is met
     */
    private function evaluateCondition(array $data, string $condition): bool
    {
        // Simple condition parser for basic comparisons
        // Format: "field operator value" (e.g., "quantity > 10")
        
        $pattern = '/^(\w+)\s*(>|<|>=|<=|==|!=)\s*(.+)$/';
        if (!preg_match($pattern, $condition, $matches)) {
            return false;
        }
        
        $field = $matches[1];
        $operator = $matches[2];
        $compareValue = trim($matches[3]);
        
        if (!isset($data[$field])) {
            return false;
        }
        
        $fieldValue = $data[$field];
        
        // Convert to numeric if both values are numeric
        if (is_numeric($fieldValue) && is_numeric($compareValue)) {
            $fieldValue = (float)$fieldValue;
            $compareValue = (float)$compareValue;
        }
        
        return match ($operator) {
            '>' => $fieldValue > $compareValue,
            '<' => $fieldValue < $compareValue,
            '>=' => $fieldValue >= $compareValue,
            '<=' => $fieldValue <= $compareValue,
            '==' => $fieldValue == $compareValue,
            '!=' => $fieldValue != $compareValue,
            default => false,
        };
    }
}
