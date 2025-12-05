<?php
/**
 * Data Transformer
 * 
 * Transforms and formats data according to business rules.
 */

declare(strict_types=1);

namespace PickingReport\Transformers;

use PickingReport\Models\OrderData;
use PickingReport\Models\Item;
use PickingReport\Models\Part;

class DataTransformer
{
    /**
     * Apply conditional display rules to data
     * 
     * @param array $data Data to transform
     * @param array $rules Display rules (e.g., ['field' => ['show_if' => ['あり', '○'], 'hide_if' => ['なし', '×']]])
     * @return array Transformed data
     */
    public function applyConditionalDisplay(array $data, array $rules): array
    {
        $result = $data;
        
        foreach ($rules as $field => $rule) {
            if (!isset($data[$field])) {
                continue;
            }
            
            $value = $data[$field];
            $shouldShow = true;
            
            // Check hide conditions first (higher priority)
            if (isset($rule['hide_if'])) {
                foreach ($rule['hide_if'] as $hideKeyword) {
                    if ($this->containsKeyword($value, $hideKeyword)) {
                        $shouldShow = false;
                        break;
                    }
                }
            }
            
            // Check show conditions
            if ($shouldShow && isset($rule['show_if'])) {
                $shouldShow = false;
                foreach ($rule['show_if'] as $showKeyword) {
                    if ($this->containsKeyword($value, $showKeyword)) {
                        $shouldShow = true;
                        break;
                    }
                }
            }
            
            // Apply display decision
            if (!$shouldShow) {
                unset($result[$field]);
            }
        }
        
        return $result;
    }

    /**
     * Apply default values to empty fields
     * 
     * @param array $data Data to transform
     * @param array $defaults Default values (e.g., ['field' => '-'])
     * @return array Transformed data
     */
    public function applyDefaultValues(array $data, array $defaults): array
    {
        $result = $data;
        
        foreach ($defaults as $field => $defaultValue) {
            if (!isset($result[$field]) || $result[$field] === '' || $result[$field] === null) {
                $result[$field] = $defaultValue;
            }
        }
        
        return $result;
    }

    /**
     * Format numeric value according to format string
     * 
     * @param float $value Numeric value
     * @param string $format Format string (e.g., '%.2f', '%.0f')
     * @return string Formatted value
     */
    public function formatNumericValue(float $value, string $format): string
    {
        return sprintf($format, $value);
    }

    /**
     * Add unit to numeric value
     * 
     * @param float $value Numeric value
     * @param string $unit Unit string (e.g., 'cm', 'kg')
     * @return string Value with unit
     */
    public function addUnit(float $value, string $unit): string
    {
        // Handle special unit formats
        if (strpos($unit, '{value}') !== false) {
            return str_replace('{value}', (string)$value, $unit);
        }
        
        return $value . $unit;
    }

    /**
     * Transform OrderData with all transformation rules
     * 
     * @param OrderData $orderData Order data to transform
     * @param array $config Transformation configuration
     * @return OrderData Transformed order data
     */
    public function transform(OrderData $orderData, array $config = []): OrderData
    {
        // Apply transformations to items
        $transformedItems = [];
        foreach ($orderData->getItems() as $item) {
            $transformedItems[] = $this->transformItem($item, $config);
        }
        
        $orderData->setItems($transformedItems);
        
        // Apply transformations to metadata
        if (isset($config['metadata_defaults'])) {
            $metadata = $this->applyDefaultValues(
                $orderData->getMetadata(),
                $config['metadata_defaults']
            );
            $orderData->setMetadata($metadata);
        }
        
        return $orderData;
    }

    /**
     * Transform Item with transformation rules
     */
    private function transformItem(Item $item, array $config): Item
    {
        // Apply transformations to attributes
        if (isset($config['item_defaults'])) {
            $attributes = $this->applyDefaultValues(
                $item->getAttributes(),
                $config['item_defaults']
            );
            $item->setAttributes($attributes);
        }
        
        if (isset($config['item_display_rules'])) {
            $attributes = $this->applyConditionalDisplay(
                $item->getAttributes(),
                $config['item_display_rules']
            );
            $item->setAttributes($attributes);
        }
        
        // Transform parts
        $transformedParts = [];
        foreach ($item->getParts() as $part) {
            $transformedParts[] = $this->transformPart($part, $config);
        }
        $item->setParts($transformedParts);
        
        return $item;
    }

    /**
     * Transform Part with transformation rules
     */
    private function transformPart(Part $part, array $config): Part
    {
        // Apply numeric formatting to dimensions
        if ($part->getWidth() !== null && isset($config['dimension_format'])) {
            $formattedWidth = $this->formatNumericValue(
                $part->getWidth(),
                $config['dimension_format']
            );
            
            if (isset($config['dimension_unit'])) {
                $formattedWidth = $this->addUnit(
                    $part->getWidth(),
                    $config['dimension_unit']
                );
            }
            
            $part->setSpecification('formatted_width', $formattedWidth);
        }
        
        if ($part->getHeight() !== null && isset($config['dimension_format'])) {
            $formattedHeight = $this->formatNumericValue(
                $part->getHeight(),
                $config['dimension_format']
            );
            
            if (isset($config['dimension_unit'])) {
                $formattedHeight = $this->addUnit(
                    $part->getHeight(),
                    $config['dimension_unit']
                );
            }
            
            $part->setSpecification('formatted_height', $formattedHeight);
        }
        
        // Apply numeric conversion rules to all specifications
        if (isset($config['numeric_conversion_rules'])) {
            $specs = $this->applyNumericConversionRules(
                $part->getSpecifications(),
                $config['numeric_conversion_rules']
            );
            $part->setSpecifications($specs);
        }
        
        // Apply default values to specifications
        if (isset($config['part_defaults'])) {
            $specs = $this->applyDefaultValues(
                $part->getSpecifications(),
                $config['part_defaults']
            );
            $part->setSpecifications($specs);
        }
        
        return $part;
    }

    /**
     * Check if value contains keyword (case-insensitive)
     */
    private function containsKeyword(mixed $value, string $keyword): bool
    {
        if (!is_string($value)) {
            $value = (string)$value;
        }
        
        return stripos($value, $keyword) !== false;
    }

    /**
     * Apply field-specific numeric conversion rules
     * 
     * @param array $data Data to transform
     * @param array $rules Conversion rules (e.g., ['field' => ['format' => '%.2f', 'unit' => 'cm']])
     * @return array Transformed data
     */
    public function applyNumericConversionRules(array $data, array $rules): array
    {
        $result = $data;
        
        foreach ($rules as $field => $rule) {
            if (!isset($data[$field])) {
                continue;
            }
            
            $value = $data[$field];
            
            // Convert to float if needed
            if (!is_numeric($value)) {
                continue;
            }
            
            $numericValue = (float)$value;
            
            // Apply format
            if (isset($rule['format'])) {
                $formatted = $this->formatNumericValue($numericValue, $rule['format']);
                $result[$field] = $formatted;
            }
            
            // Apply unit
            if (isset($rule['unit'])) {
                $result[$field] = $this->addUnit($numericValue, $rule['unit']);
            }
            
            // Apply rounding
            if (isset($rule['decimals'])) {
                $result[$field] = round($numericValue, $rule['decimals']);
            }
        }
        
        return $result;
    }
}
