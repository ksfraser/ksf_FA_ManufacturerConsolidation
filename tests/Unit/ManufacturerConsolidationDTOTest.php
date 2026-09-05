<?php
declare(strict_types=1);

namespace Ksfraser\Tests\FrontAccounting\ManufacturerConsolidation;

use Ksfraser\FrontAccounting\ManufacturerConsolidation\MoqRuleDTO;
use Ksfraser\FrontAccounting\ManufacturerConsolidation\ConsolidationRecommendationDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Manufacturer Consolidation DTOs.
 *
 * @BABOK Related: BR-MC-001
 * @since 1.0.0
 */
class ManufacturerConsolidationDTOTest extends TestCase
{
    public function testMoqRuleApplyToQtyBelowMoq(): void
    {
        $dto = new MoqRuleDTO(
            1,
            50.0,
            new \DateTimeImmutable('2026-01-01')
        );

        $adjusted = $dto->applyToQty(25.0);
        $this->assertEquals(50.0, $adjusted);
    }

    public function testMoqRuleApplyToQtyAboveMoq(): void
    {
        $dto = new MoqRuleDTO(
            1,
            50.0,
            new \DateTimeImmutable('2026-01-01')
        );

        $adjusted = $dto->applyToQty(75.0);
        $this->assertEquals(75.0, $adjusted);
    }

    public function testMoqRuleApplyToQtyWithMultiple(): void
    {
        $dto = new MoqRuleDTO(
            1,
            10.0,
            new \DateTimeImmutable('2026-01-01')
        );
        $dto->orderMultiple = 5.0;

        $adjusted = $dto->applyToQty(12.0);
        $this->assertEquals(15.0, $adjusted);
    }

    public function testMoqRuleGetGap(): void
    {
        $dto = new MoqRuleDTO(
            1,
            50.0,
            new \DateTimeImmutable('2026-01-01')
        );

        $gap = $dto->getGap(30.0);
        $this->assertEquals(20.0, $gap);
    }

    public function testMoqRuleIsActive(): void
    {
        $dto = new MoqRuleDTO(
            1,
            50.0,
            new \DateTimeImmutable('2025-01-01')
        );

        $this->assertTrue($dto->isActive());
    }

    public function testMoqRuleIsInactive(): void
    {
        $dto = new MoqRuleDTO(
            1,
            50.0,
            new \DateTimeImmutable('2027-01-01')
        );

        $this->assertFalse($dto->isActive());
    }

    public function testConsolidationRecommendationCalculateAdditionalCost(): void
    {
        $dto = new ConsolidationRecommendationDTO(
            1,
            'TEST-001',
            25.0,
            50.0,
            50.0,
            ConsolidationRecommendationDTO::TYPE_MOQ_ROUNDING
        );

        $dto->calculateAdditionalCost(10.0);
        $this->assertEquals(250.0, $dto->additionalCost);
    }

    public function testConsolidationRecommendationSetDaysToSellExtra(): void
    {
        $dto = new ConsolidationRecommendationDTO(
            1,
            'TEST-001',
            25.0,
            50.0,
            50.0,
            ConsolidationRecommendationDTO::TYPE_MOQ_ROUNDING
        );

        $dto->setDaysToSellExtra(20.0);
        $this->assertEquals(20.0, $dto->daysToSellExtra);
        $this->assertEquals('positive', $dto->getRoiImpact());
    }
}