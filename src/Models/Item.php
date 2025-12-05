<?php
/**
 * Item Model
 * 
 * Represents an item in an order.
 */

declare(strict_types=1);

namespace PickingReport\Models;

class Item
{
    private string $itemCode;
    private string $itemName;
    private int $quantity;
    /** @var Part[] */
    private array $parts;
    private array $attributes;

    /**
     * @param Part[] $parts
     */
    public function __construct(
        string $itemCode,
        string $itemName,
        int $quantity,
        array $parts = [],
        array $attributes = []
    ) {
        $this->itemCode = $itemCode;
        $this->itemName = $itemName;
        $this->quantity = $quantity;
        $this->parts = $parts;
        $this->attributes = $attributes;
    }

    public function getItemCode(): string
    {
        return $this->itemCode;
    }

    public function setItemCode(string $itemCode): void
    {
        $this->itemCode = $itemCode;
    }

    public function getItemName(): string
    {
        return $this->itemName;
    }

    public function setItemName(string $itemName): void
    {
        $this->itemName = $itemName;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    /**
     * @return Part[]
     */
    public function getParts(): array
    {
        return $this->parts;
    }

    /**
     * @param Part[] $parts
     */
    public function setParts(array $parts): void
    {
        $this->parts = $parts;
    }

    public function addPart(Part $part): void
    {
        $this->parts[] = $part;
    }

    public function getPartsCount(): int
    {
        return count($this->parts);
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function validate(): ValidationResult
    {
        $errors = [];

        if (empty($this->itemCode)) {
            $errors[] = 'Item code is required';
        }

        if (empty($this->itemName)) {
            $errors[] = 'Item name is required';
        }

        if ($this->quantity < 0) {
            $errors[] = 'Item quantity cannot be negative';
        }

        // Validate all parts
        foreach ($this->parts as $index => $part) {
            $partValidation = $part->validate();
            if (!$partValidation->isValid()) {
                foreach ($partValidation->getErrors() as $error) {
                    $errors[] = "Part {$index}: {$error}";
                }
            }
        }

        return empty($errors) 
            ? ValidationResult::success() 
            : ValidationResult::failure($errors);
    }

    public function toArray(): array
    {
        return [
            'itemCode' => $this->itemCode,
            'itemName' => $this->itemName,
            'quantity' => $this->quantity,
            'parts' => array_map(fn(Part $part) => $part->toArray(), $this->parts),
            'attributes' => $this->attributes,
        ];
    }
}
