<?php
declare(strict_types=1);

namespace Ksfraser\FrontAccounting\ManufacturerConsolidation;

/**
 * Data transfer object for consolidation recommendation.
 *
 * @since 1.0.0
 */
class ConsolidationRecommendationDTO
{
    public const TYPE_MOQ_ROUNDING = 'moq_rounding';
    public const TYPE_CONSOLIDATION = 'consolidation';
    public const TYPE_BULK_DISCOUNT = 'bulk_discount';
    public const TYPE_LEAD_TIME = 'lead_time';

    /** @var int|null */
    private $id;

    /** @var int */
    private $supplierId;

    /** @var string */
    private $stockId;

    /** @var float */
    private $currentSuggestedQty;

    /** @var float */
    private $moq;

    /** @var float */
    private $recommendedQty;

    /** @var float */
    private $additionalCost;

    /** @var float|null */
    private $daysToSellExtra;

    /** @var string|null */
    private $roiImpact;

    /** @var string */
    private $recommendationType;

    /** @var string|null */
    private $rationale;

    /** @var string */
    private $status;

    public function __construct(
        int $supplierId,
        string $stockId,
        float $currentSuggestedQty,
        float $moq,
        float $recommendedQty,
        string $recommendationType
    ) {
        $this->supplierId = $supplierId;
        $this->stockId = $stockId;
        $this->currentSuggestedQty = $currentSuggestedQty;
        $this->moq = $moq;
        $this->recommendedQty = $recommendedQty;
        $this->recommendationType = $recommendationType;
        $this->status = 'pending';
    }

    public static function fromArray(array $data): self
    {
        $dto = new self(
            (int) $data['supplier_id'],
            $data['stock_id'],
            (float) $data['current_suggested_qty'],
            (float) $data['moq'],
            (float) $data['recommended_qty'],
            $data['recommendation_type']
        );
        $dto->id = isset($data['id']) ? (int) $data['id'] : null;
        $dto->additionalCost = (float) ($data['additional_cost'] ?? 0);
        $dto->daysToSellExtra = isset($data['days_to_sell_extra']) ? (float) $data['days_to_sell_extra'] : null;
        $dto->roiImpact = $data['roi_impact'] ?? null;
        $dto->rationale = $data['rationale'] ?? null;
        $dto->status = $data['status'] ?? 'pending';
        return $dto;
    }

    public function calculateAdditionalCost(float $unitCost): void
    {
        $this->additionalCost = ($this->recommendedQty - $this->currentSuggestedQty) * $unitCost;
    }

    public function setDaysToSellExtra(float $days): void
    {
        $this->daysToSellExtra = $days;
        $this->roiImpact = $days <= 30 ? 'positive' : ($days <= 90 ? 'neutral' : 'negative');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getRoiImpact(): string
    {
        return $this->roiImpact ?? 'neutral';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'supplier_id' => $this->supplierId,
            'stock_id' => $this->stockId,
            'current_suggested_qty' => $this->currentSuggestedQty,
            'moq' => $this->moq,
            'recommended_qty' => $this->recommendedQty,
            'additional_cost' => $this->additionalCost,
            'days_to_sell_extra' => $this->daysToSellExtra,
            'roi_impact' => $this->roiImpact,
            'recommendation_type' => $this->recommendationType,
            'rationale' => $this->rationale,
            'status' => $this->status,
        ];
    }
}