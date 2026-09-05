# FR-MC-001-002: Generate Consolidation Recommendations

## Version
1.0.0

## Author
KSF Development Team

## Created
2026-09-04

## Status
Approved

## Functional Requirement

## FR-MC-001-002: Generate Consolidation Recommendations

### Description
Group multiple suggested orders to meet MOQ and generate recommendations.

### Input
- Multiple suggested orders for same supplier
- MOQ rule for supplier
- Days-to-sell for extra inventory

### Processing
1. Group suggestions by supplier
2. Sum quantities per item
3. Apply MOQ rules
4. Calculate total additional cost
5. Estimate total extra inventory days

### Output
- Consolidation recommendation with combined items
- Cost/benefit analysis

### Business Rules
- Minimum savings threshold to recommend consolidation
- ROI positive if extra holding cost < savings
- Consolidation only suggested for same supplier

### Acceptance Criteria
- [ ] Multiple orders consolidated correctly
- [ ] Cost of extra inventory calculated
- [ ] ROI impact assessed
- [ ] Recommendations broadcast via hook