<?php
declare(strict_types=1);

namespace Ksfraser\FrontAccounting\ManufacturerConsolidation;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Handles manufacturer consolidation logic.
 *
 * @since 1.0.0
 */
class ConsolidationHandler
{
    /** @var ConsolidationRepository */
    private $repository;

    /** @var LoggerInterface */
    private $logger;

    /** @var array */
    private $pendingRecommendations = [];

    public function __construct(
        ConsolidationRepository $repository,
        ?LoggerInterface $logger = null
    ) {
        $this->repository = $repository;
        $this->logger = $logger ?? new NullLogger();
    }

    public function checkMoqGaps(): void
    {
        $this->logger->info('Checking MOQ gaps');

        $sql = "SELECT DISTINCT supplier_id FROM purch_orders WHERE ord_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        $suppliers = $this->db->fetchAll($sql, []);

        foreach ($suppliers as $row) {
            $supplierId = (int) $row['supplier_id'];
            $this->checkSupplierMoqGaps($supplierId);
        }
    }

    private function checkSupplierMoqGaps(int $supplierId): void
    {
        $rules = $this->repository->getMoqRulesForSupplier($supplierId);

        if (empty($rules)) {
            return;
        }

        foreach ($rules as $rule) {
            if (!$rule->isActive()) {
                continue;
            }

            $suggestedQty = $this->getSuggestedQty($supplierId, $rule->getStockId());

            if ($suggestedQty > 0 && $suggestedQty < $rule->getMinOrderQty()) {
                $this->createRecommendation($supplierId, $rule, $suggestedQty);
            }
        }
    }

    private function getSuggestedQty(int $supplierId, ?string $stockId): float
    {
        $sql = "SELECT SUM(quantity) as total_qty
                FROM 0_suggested_order_lines sol
                INNER JOIN 0_suggested_orders so ON sol.order_id = so.id
                WHERE so.supplier_id = ? AND so.status = 'draft'";

        $params = [$supplierId];

        if ($stockId !== null) {
            $sql .= " AND sol.stock_id = ?";
            $params[] = $stockId;
        }

        $result = $this->db->fetchAssoc($sql, $params);
        return $result ? (float) ($result['total_qty'] ?? 0) : 0;
    }

    private function createRecommendation(
        int $supplierId,
        MoqRuleDTO $rule,
        float $currentSuggestedQty
    ): void {
        $unitCost = $this->getUnitCost($rule->getStockId());
        $recommendedQty = $rule->applyToQty($currentSuggestedQty);
        $gap = $recommendedQty - $currentSuggestedQty;

        if ($gap <= 0) {
            return;
        }

        $recommendation = new ConsolidationRecommendationDTO(
            $supplierId,
            $rule->getStockId() ?? '',
            $currentSuggestedQty,
            $rule->getMinOrderQty(),
            $recommendedQty,
            ConsolidationRecommendationDTO::TYPE_MOQ_ROUNDING
        );

        $recommendation->calculateAdditionalCost($unitCost);

        $daysToSell = $this->calculateDaysToSell($rule->getStockId(), $gap);
        $recommendation->setDaysToSellExtra($daysToSell);

        $recommendation->rationale = sprintf(
            'MOQ of %.2f applies. Suggested qty %.2f rounded to %.2f. Extra cost: $%.2f',
            $rule->getMinOrderQty(),
            $currentSuggestedQty,
            $recommendedQty,
            $recommendation->additionalCost
        );

        $this->repository->saveRecommendation($recommendation);
        $this->pendingRecommendations[] = $recommendation;

        $this->logger->info('Created MOQ recommendation', [
            'supplier_id' => $supplierId,
            'stock_id' => $rule->getStockId(),
            'gap' => $gap,
            'additional_cost' => $recommendation->additionalCost,
        ]);
    }

    private function getUnitCost(?string $stockId): float
    {
        if ($stockId === null) {
            return 0;
        }

        $sql = "SELECT material_cost + labor_cost + overhead_cost as unit_cost
                FROM stock_master WHERE stock_id = ?";

        $result = $this->db->fetchAssoc($sql, [$stockId]);
        return $result ? (float) ($result['unit_cost'] ?? 0) : 0;
    }

    private function calculateDaysToSell(?string $stockId, float $qty): float
    {
        if ($stockId === null || $qty <= 0) {
            return 0;
        }

        $sql = "SELECT avg_daily_consumption
                FROM 0_ksf_stock_turnover_metrics
                WHERE stock_id = ? AND calc_date = CURDATE() - INTERVAL 1 DAY";

        $result = $this->db->fetchAssoc($sql, [$stockId]);

        if (!$result || ($result['avg_daily_consumption'] ?? 0) <= 0) {
            return 30;
        }

        return $qty / (float) $result['avg_daily_consumption'];
    }

    public function evaluateForConsolidation(array $data): void
    {
        $supplierId = (int) ($data['supplier_id'] ?? 0);
        $lines = $data['lines'] ?? [];

        if ($supplierId <= 0 || empty($lines)) {
            return;
        }

        $this->logger->info('Evaluating for consolidation', [
            'supplier_id' => $supplierId,
            'lines' => count($lines),
        ]);

        $rule = $this->repository->getMoqRule($supplierId);

        if ($rule === null) {
            return;
        }

        $totalQty = array_sum(array_column($lines, 'quantity'));

        if ($totalQty < $rule->getMinOrderQty()) {
            $recommendedQty = $rule->applyToQty($totalQty);
            $this->logger->info('Consolidation needed', [
                'total_qty' => $totalQty,
                'moq' => $rule->getMinOrderQty(),
                'recommended' => $recommendedQty,
            ]);
        }
    }

    public function updateFromTurnoverData(array $data): void
    {
        $this->logger->debug('Received turnover data');
    }

    public function updateFromPoTrackingData(array $data): void
    {
        $this->logger->debug('Received PO tracking data');
    }

    public function getPendingRecommendations(): array
    {
        return $this->pendingRecommendations;
    }
}