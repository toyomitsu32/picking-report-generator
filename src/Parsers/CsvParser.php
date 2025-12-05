<?php
/**
 * CSV Parser
 * 
 * Parses CSV files and converts them to structured data.
 */

declare(strict_types=1);

namespace PickingReport\Parsers;

use PickingReport\Models\OrderData;
use PickingReport\Models\Item;
use PickingReport\Models\Part;
use PickingReport\Models\ParsedData;
use PickingReport\Models\ValidationResult;
use PickingReport\Exceptions\ValidationException;

class CsvParser
{
    private const ENCODING = 'UTF-8';
    
    /**
     * Parse CSV file and return structured data
     */
    public function parse(string $filePath): ParsedData
    {
        // Validate file exists
        if (!file_exists($filePath)) {
            throw new ValidationException("CSV file not found: {$filePath}");
        }

        // Validate file is readable
        if (!is_readable($filePath)) {
            throw new ValidationException("CSV file is not readable: {$filePath}");
        }

        // Read CSV file
        $rows = $this->readCsvFile($filePath);
        
        // Validate CSV has data
        if (empty($rows)) {
            throw new ValidationException("CSV file is empty");
        }

        // Parse the CSV data
        $orderData = $this->parseOrderData($rows);
        
        // Validate the parsed data
        $validation = $orderData->validate();
        if (!$validation->isValid()) {
            throw new ValidationException(
                "Invalid order data",
                $validation->getErrors()
            );
        }

        return new ParsedData($orderData, $rows);
    }

    /**
     * Read CSV file and return rows as array
     */
    private function readCsvFile(string $filePath): array
    {
        $rows = [];
        $handle = fopen($filePath, 'r');
        
        if ($handle === false) {
            throw new ValidationException("Failed to open CSV file: {$filePath}");
        }

        try {
            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                $rows[] = $row;
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    /**
     * Parse CSV rows into OrderData
     */
    private function parseOrderData(array $rows): OrderData
    {
        // Extract header information (assuming first few rows contain order metadata)
        $orderNumber = $this->extractValue($rows, 'order_number', '受注番号');
        $orderDate = $this->extractValue($rows, 'order_date', '受注日');
        $customerName = $this->extractValue($rows, 'customer_name', '顧客名');
        $deliveryDate = $this->extractValue($rows, 'delivery_date', '納期');

        // Parse items
        $items = $this->parseItems($rows);

        return new OrderData(
            $orderNumber ?: 'UNKNOWN',
            $orderDate ?: date('Y-m-d'),
            $customerName ?: 'Unknown Customer',
            $deliveryDate ?: date('Y-m-d'),
            $items
        );
    }

    /**
     * Extract value from CSV rows by keyword
     */
    private function extractValue(array $rows, string $key, string $keyword): ?string
    {
        foreach ($rows as $row) {
            foreach ($row as $index => $cell) {
                if (stripos($cell, $keyword) !== false) {
                    // Check if value is in the same cell after colon
                    $parts = explode(':', $cell, 2);
                    if (count($parts) === 2 && trim($parts[1]) !== '') {
                        return trim($parts[1]);
                    }
                    
                    // Check if value is in the next cell
                    if (isset($row[$index + 1]) && trim($row[$index + 1]) !== '') {
                        return trim($row[$index + 1]);
                    }
                }
            }
        }
        return null;
    }

    /**
     * Split cell content by newline
     */
    public function splitCellByNewline(string $cell): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $cell);
        return array_filter(array_map('trim', $lines), fn($line) => $line !== '');
    }

    /**
     * Split cell content by comma
     */
    public function splitCellByComma(string $cell): array
    {
        $parts = explode(',', $cell);
        return array_filter(array_map('trim', $parts), fn($part) => $part !== '');
    }

    /**
     * Extract data by keyword from cell
     */
    public function extractByKeyword(string $cell, string $keyword): array
    {
        $results = [];
        $lines = $this->splitCellByNewline($cell);
        
        foreach ($lines as $line) {
            if (stripos($line, $keyword) !== false) {
                $results[] = $line;
            }
        }
        
        return $results;
    }

    /**
     * Parse items from CSV rows
     */
    public function parseItems(array $rows): array
    {
        $items = [];
        $currentItem = null;
        
        foreach ($rows as $rowIndex => $row) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Check if row contains item keyword
            $itemKeywords = ['アイテム:', 'Item:', 'item:'];
            $isItemRow = false;
            
            foreach ($row as $cell) {
                foreach ($itemKeywords as $keyword) {
                    if (stripos($cell, $keyword) !== false) {
                        $isItemRow = true;
                        break 2;
                    }
                }
            }

            if ($isItemRow) {
                // Save previous item if exists
                if ($currentItem !== null) {
                    $items[] = $currentItem;
                }
                
                // Create new item
                $itemCode = $this->extractItemCode($row);
                $itemName = $this->extractItemName($row);
                $quantity = $this->extractQuantity($row);
                
                $currentItem = new Item(
                    $itemCode ?: "ITEM-" . (count($items) + 1),
                    $itemName ?: "Item " . (count($items) + 1),
                    $quantity
                );
            } elseif ($currentItem !== null) {
                // Parse parts for current item
                $parts = $this->parseParts([$row]);
                foreach ($parts as $part) {
                    $currentItem->addPart($part);
                }
            }
        }
        
        // Add last item
        if ($currentItem !== null) {
            $items[] = $currentItem;
        }
        
        return $items;
    }

    /**
     * Parse parts from item data
     */
    public function parseParts(array $itemData): array
    {
        $parts = [];
        
        foreach ($itemData as $row) {
            // Check if row contains part keyword
            $partKeywords = ['パーツ:', 'Part:', 'part:', '部品:'];
            $isPartRow = false;
            
            foreach ($row as $cell) {
                foreach ($partKeywords as $keyword) {
                    if (stripos($cell, $keyword) !== false) {
                        $isPartRow = true;
                        break 2;
                    }
                }
            }

            if ($isPartRow) {
                $partCode = $this->extractPartCode($row);
                $partName = $this->extractPartName($row);
                $quantity = $this->extractQuantity($row);
                $width = $this->extractDimension($row, 'width', '幅', 'タテ');
                $height = $this->extractDimension($row, 'height', '高さ', 'ヨコ');
                
                $parts[] = new Part(
                    $partCode ?: "PART-" . (count($parts) + 1),
                    $partName ?: "Part " . (count($parts) + 1),
                    $quantity,
                    $width,
                    $height
                );
            }
        }
        
        return $parts;
    }

    /**
     * Extract item code from row
     */
    private function extractItemCode(array $row): ?string
    {
        foreach ($row as $cell) {
            if (preg_match('/[A-Z0-9]{3,}/', $cell, $matches)) {
                return $matches[0];
            }
        }
        return null;
    }

    /**
     * Extract item name from row
     */
    private function extractItemName(array $row): ?string
    {
        foreach ($row as $cell) {
            $lines = $this->splitCellByNewline($cell);
            foreach ($lines as $line) {
                if (stripos($line, 'アイテム:') !== false || stripos($line, 'Item:') !== false) {
                    $parts = explode(':', $line, 2);
                    if (count($parts) === 2) {
                        return trim($parts[1]);
                    }
                }
            }
        }
        return null;
    }

    /**
     * Extract part code from row
     */
    private function extractPartCode(array $row): ?string
    {
        return $this->extractItemCode($row);
    }

    /**
     * Extract part name from row
     */
    private function extractPartName(array $row): ?string
    {
        foreach ($row as $cell) {
            $lines = $this->splitCellByNewline($cell);
            foreach ($lines as $line) {
                if (stripos($line, 'パーツ:') !== false || stripos($line, 'Part:') !== false) {
                    $parts = explode(':', $line, 2);
                    if (count($parts) === 2) {
                        return trim($parts[1]);
                    }
                }
            }
        }
        return null;
    }

    /**
     * Extract quantity from row
     */
    private function extractQuantity(array $row): int
    {
        foreach ($row as $cell) {
            if (preg_match('/数量[:\s]*(\d+)/', $cell, $matches)) {
                return (int)$matches[1];
            }
            if (preg_match('/(\d+)\s*個/', $cell, $matches)) {
                return (int)$matches[1];
            }
        }
        return 1; // Default quantity
    }

    /**
     * Extract dimension (width/height) from row
     */
    private function extractDimension(array $row, string $type, string ...$keywords): ?float
    {
        foreach ($row as $cell) {
            foreach ($keywords as $keyword) {
                if (stripos($cell, $keyword) !== false) {
                    if (preg_match('/(\d+\.?\d*)\s*cm/', $cell, $matches)) {
                        return (float)$matches[1];
                    }
                    if (preg_match('/(\d+\.?\d*)/', $cell, $matches)) {
                        return (float)$matches[1];
                    }
                }
            }
        }
        return null;
    }
}
