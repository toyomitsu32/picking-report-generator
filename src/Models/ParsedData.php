<?php
/**
 * ParsedData Model
 * 
 * Represents parsed CSV data with order information.
 */

declare(strict_types=1);

namespace PickingReport\Models;

class ParsedData
{
    private OrderData $order;
    private array $rawData;

    public function __construct(OrderData $order, array $rawData = [])
    {
        $this->order = $order;
        $this->rawData = $rawData;
    }

    public function getOrder(): OrderData
    {
        return $this->order;
    }

    public function setOrder(OrderData $order): void
    {
        $this->order = $order;
    }

    public function getRawData(): array
    {
        return $this->rawData;
    }

    public function setRawData(array $rawData): void
    {
        $this->rawData = $rawData;
    }

    public function toArray(): array
    {
        return [
            'order' => $this->order->toArray(),
            'rawData' => $this->rawData,
        ];
    }
}
