<?php
declare(strict_types=1);

namespace Ksfraser\FrontAccounting\ManufacturerConsolidation;

use Ksfraser\CommonDb\Contract\DbConnectionInterface;

/**
 * Repository for manufacturer consolidation data.
 *
 * @since 1.0.0
 */
class ConsolidationRepository
{
    /** @var DbConnectionInterface */
    private $db;

    /** @var string */
    private $moqTable;

    /** @var string */
    private $recommendationsTable;

    public function __construct(
        DbConnectionInterface $db,
        string $moqTable = '0_ksf_supplier_moq_rules',
        string $recommendationsTable = '0_ksf_consolidation_recommendations'
    ) {
        $this->db = $db;
        $this->moqTable = $moqTable;
        $this->recommendationsTable = $recommendationsTable;
    }

    public function getMoqRule(int $supplierId, ?string $stockId = null): ?MoqRuleDTO
    {
        $sql = "SELECT * FROM {$this->moqTable}
                WHERE supplier_id = ? AND effective_from <= CURDATE()
                AND (effective_to IS NULL OR effective_to >= CURDATE())
                AND (stock_id = ? OR stock_id IS NULL)
                ORDER BY stock_id DESC LIMIT 1";

        $row = $this->db->fetchAssoc($sql, [$supplierId, $stockId]);

        if ($row === false) {
            return null;
        }

        return MoqRuleDTO::fromArray($row);
    }

    public function getMoqRulesForSupplier(int $supplierId): array
    {
        $sql = "SELECT * FROM {$this->moqTable}
                WHERE supplier_id = ? AND effective_from <= CURDATE()
                AND (effective_to IS NULL OR effective_to >= CURDATE())
                ORDER BY stock_id DESC";

        $rows = $this->db->fetchAll($sql, [$supplierId]);
        return array_map(fn($row) => MoqRuleDTO::fromArray($row), $rows);
    }

    public function saveRecommendation(ConsolidationRecommendationDTO $recommendation): void
    {
        $sql = "INSERT INTO {$this->recommendationsTable}
                (supplier_id, stock_id, current_suggested_qty, moq, recommended_qty,
                 additional_cost, days_to_sell_extra, roi_impact, recommendation_type, rationale, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                recommended_qty = VALUES(recommended_qty),
                additional_cost = VALUES(additional_cost),
                days_to_sell_extra = VALUES(days_to_sell_extra),
                roi_impact = VALUES(roi_impact),
                status = VALUES(status)";

        $this->db->executeUpdate($sql, [
            $recommendation->supplierId,
            $recommendation->stockId,
            $recommendation->currentSuggestedQty,
            $recommendation->moq,
            $recommendation->recommendedQty,
            $recommendation->additionalCost,
            $recommendation->daysToSellExtra,
            $recommendation->roiImpact,
            $recommendation->recommendationType,
            $recommendation->rationale,
            $recommendation->status,
        ]);
    }

    public function getPendingRecommendations(int $supplierId = null): array
    {
        $sql = "SELECT r.*, sm.stock_id as item_code, sm.description
                FROM {$this->recommendationsTable} r
                LEFT JOIN stock_master sm ON r.stock_id = sm.stock_id
                WHERE r.status = 'pending'";

        $params = [];

        if ($supplierId !== null) {
            $sql .= " AND r.supplier_id = ?";
            $params[] = $supplierId;
        }

        $sql .= " ORDER BY r.additional_cost DESC";

        $rows = $this->db->fetchAll($sql, $params);
        return array_map(fn($row) => ConsolidationRecommendationDTO::fromArray($row), $rows);
    }

    public function getRecommendationsByRoi(string $roiImpact = 'positive'): array
    {
        $sql = "SELECT * FROM {$this->recommendationsTable}
                WHERE roi_impact = ? AND status = 'pending'
                ORDER BY additional_cost ASC";

        $rows = $this->db->fetchAll($sql, [$roiImpact]);
        return array_map(fn($row) => ConsolidationRecommendationDTO::fromArray($row), $rows);
    }
}