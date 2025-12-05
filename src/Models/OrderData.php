<?php
/**
 * OrderData Model
 * 
 * Represents order data containing items and metadata.
 */

declare(strict_types=1);

namespace PickingReport\Models;

class OrderData
{
    private string $orderNumber;
    private string $orderDate;
    private string $customerName;
    private string $deliveryDate;
    /** @var Item[] */
    private array $items;
    private array $metadata;

    /**
     * @param Item[] $items
     */
    public function __construct(
        string $orderNumber,
        string $orderDate,
        string $customerName,
        string $deliveryDate,
        array $items = [],
        array $metadata = []
    ) {
        $this->orderNumber = $orderNumber;
        $this->orderDate = $orderDate;
        $this->customerName = $customerName;
        $this->deliveryDate = $deliveryDate;
        $this->items = $items;
        $this->metadata = $metadata;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    public function getOrderDate(): string
    {
        return $this->orderDate;
    }

    public function setOrderDate(string $orderDate): void
    {
        $this->orderDate = $orderDate;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function setCustomerName(string $customerName): void
    {
        $this->customerName = $customerName;
    }

    public function getDeliveryDate(): string
    {
        return $this->deliveryDate;
    }

    public function setDeliveryDate(string $deliveryDate): void
    {
        $this->deliveryDate = $deliveryDate;
    }

    /**
     * @return Item[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param Item[] $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    public function addItem(Item $item): void
    {
        $this->items[] = $item;
    }

    public function getItemsCount(): int
    {
        return count($this->items);
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getMetadataValue(string $key): mixed
    {
        return $this->metadata[$key] ?? null;
    }

    public function setMetadataValue(string $key, mixed $value): void
    {
        $this->metadata[$key] = $value;
    }

    public function validate(): ValidationResult
    {
        $errors = [];

        if (empty($this->orderNumber)) {
            $errors[] = 'Order number is required';
        }

        if (empty($this->orderDate)) {
            $errors[] = 'Order date is required';
        }

        if (empty($this->customerName)) {
            $errors[] = 'Customer name is required';
        }

        if (empty($this->deliveryDate)) {
            $errors[] = 'Delivery date is required';
        }

        if (empty($this->items)) {
            $errors[] = 'Order must contain at least one item';
        }

        // Validate all items
        foreach ($this->items as $index => $item) {
            $itemValidation = $item->validate();
            if (!$itemValidation->isValid()) {
                foreach ($itemValidation->getErrors() as $error) {
                    $errors[] = "Item {$index}: {$error}";
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
            'orderNumber' => $this->orderNumber,
            'orderDate' => $this->orderDate,
            'customerName' => $this->customerName,
            'deliveryDate' => $this->deliveryDate,
            'items' => array_map(fn(Item $item) => $item->toArray(), $this->items),
            'metadata' => $this->metadata,
        ];
    }
}
