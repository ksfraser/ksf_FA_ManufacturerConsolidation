<?php
declare(strict_types=1);

namespace Ksfraser\FrontAccounting\ManufacturerConsolidation;

/**
 * Data transfer object for MOQ rule.
 *
 * @since 1.0.0
 */
class MoqRuleDTO
{
    /** @var int|null */
    private $id;

    /** @var int */
    private $supplierId;

    /** @var string|null */
    private $stockId;

    /** @var string|null */
    private $manufacturerId;

    /** @var float */
    private $minOrderQty;

    /** @var float|null */
    private $orderMultiple;

    /** @var float|null */
    private $minOrderValue;

    /** @var \DateTimeImmutable */
    private $effectiveFrom;

    /** @var \DateTimeImmutable|null */
    private $effectiveTo;

    public function __construct(
        int $supplierId,
        float $minOrderQty,
        \DateTimeImmutable $effectiveFrom,
        ?string $stockId = null,
        ?string $manufacturerId = null
    ) {
        $this->supplierId = $supplierId;
        $this->stockId = $stockId;
        $this->manufacturerId = $manufacturerId;
        $this->minOrderQty = $minOrderQty;
        $this->effectiveFrom = $effectiveFrom;
    }

    public static function fromArray(array $data): self
    {
        $dto = new self(
            (int) $data['supplier_id'],
            (float) $data['min_order_qty'],
            new \DateTimeImmutable($data['effective_from']),
            $data['stock_id'] ?? null,
            $data['manufacturer_id'] ?? null
        );
        $dto->id = isset($data['id']) ? (int) $data['id'] : null;
        $dto->orderMultiple = isset($data['order_multiple']) ? (float) $data['order_multiple'] : null;
        $dto->minOrderValue = isset($data['min_order_value']) ? (float) $data['min_order_value'] : null;
        $dto->effectiveTo = isset($data['effective_to'])
            ? new \DateTimeImmutable($data['effective_to']) : null;
        return $dto;
    }

    public function applyToQty(float $requestedQty): float
    {
        $qty = $requestedQty;

        if ($this->minOrderQty > 0 && $qty < $this->minOrderQty) {
            $qty = $this->minOrderQty;
        }

        if ($this->orderMultiple !== null && $this->orderMultiple > 0) {
            $remainder = $qty % $this->orderMultiple;
            if ($remainder > 0) {
                $qty = $qty + ($this->orderMultiple - $remainder);
            }
        }

        return $qty;
    }

    public function getGap(float $requestedQty): float
    {
        $adjustedQty = $this->applyToQty($requestedQty);
        return $adjustedQty - $requestedQty;
    }

    public function isActive(): bool
    {
        $now = new \DateTimeImmutable();
        if ($now < $this->effectiveFrom) {
            return false;
        }
        if ($this->effectiveTo !== null && $now > $this->effectiveTo) {
            return false;
        }
        return true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSupplierId(): int
    {
        return $this->supplierId;
    }

    public function getMinOrderQty(): float
    {
        return $this->minOrderQty;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'supplier_id' => $this->supplierId,
            'stock_id' => $this->stockId,
            'manufacturer_id' => $this->manufacturerId,
            'min_order_qty' => $this->minOrderQty,
            'order_multiple' => $this->orderMultiple,
            'min_order_value' => $this->minOrderValue,
            'effective_from' => $this->effectiveFrom->format('Y-m-d'),
            'effective_to' => $this->effectiveTo !== null ? $this->effectiveTo->format('Y-m-d') : null,
        ];
    }
}