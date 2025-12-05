<?php
/**
 * Part Model
 * 
 * Represents a part/component within an item.
 */

declare(strict_types=1);

namespace PickingReport\Models;

class Part
{
    private string $partCode;
    private string $partName;
    private int $quantity;
    private ?float $width;
    private ?float $height;
    private array $specifications;

    public function __construct(
        string $partCode,
        string $partName,
        int $quantity,
        ?float $width = null,
        ?float $height = null,
        array $specifications = []
    ) {
        $this->partCode = $partCode;
        $this->partName = $partName;
        $this->quantity = $quantity;
        $this->width = $width;
        $this->height = $height;
        $this->specifications = $specifications;
    }

    public function getPartCode(): string
    {
        return $this->partCode;
    }

    public function setPartCode(string $partCode): void
    {
        $this->partCode = $partCode;
    }

    public function getPartName(): string
    {
        return $this->partName;
    }

    public function setPartName(string $partName): void
    {
        $this->partName = $partName;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getWidth(): ?float
    {
        return $this->width;
    }

    public function setWidth(?float $width): void
    {
        $this->width = $width;
    }

    public function getHeight(): ?float
    {
        return $this->height;
    }

    public function setHeight(?float $height): void
    {
        $this->height = $height;
    }

    public function getSpecifications(): array
    {
        return $this->specifications;
    }

    public function setSpecifications(array $specifications): void
    {
        $this->specifications = $specifications;
    }

    public function getSpecification(string $key): mixed
    {
        return $this->specifications[$key] ?? null;
    }

    public function setSpecification(string $key, mixed $value): void
    {
        $this->specifications[$key] = $value;
    }

    public function validate(): ValidationResult
    {
        $errors = [];

        if (empty($this->partCode)) {
            $errors[] = 'Part code is required';
        }

        if (empty($this->partName)) {
            $errors[] = 'Part name is required';
        }

        if ($this->quantity < 0) {
            $errors[] = 'Part quantity cannot be negative';
        }

        if ($this->width !== null && $this->width < 0) {
            $errors[] = 'Part width cannot be negative';
        }

        if ($this->height !== null && $this->height < 0) {
            $errors[] = 'Part height cannot be negative';
        }

        return empty($errors) 
            ? ValidationResult::success() 
            : ValidationResult::failure($errors);
    }

    public function toArray(): array
    {
        return [
            'partCode' => $this->partCode,
            'partName' => $this->partName,
            'quantity' => $this->quantity,
            'width' => $this->width,
            'height' => $this->height,
            'specifications' => $this->specifications,
        ];
    }
}
