# UC-MC-001: Consolidate Orders to Meet MOQ

## Version
1.0.0

## Author
KSF Development Team

## Created
2026-09-04

## Status
Approved

## Use Case

## UC-MC-001: Consolidate Orders to Meet MOQ

### Primary Actor
Purchasing Agent

### Goal
Ensure purchase orders meet supplier minimum quantities without overstocking

### Trigger
SuggestedPO approved or nightly cron

### Main Flow

1. SuggestedPO broadcasts `suggested_po_approved`
2. Consolidation module receives hook
3. Looks up MOQ rule for supplier
4. If suggested qty < MOQ:
   - Calculate extra qty needed
   - Calculate additional cost
   - Estimate days-to-sell extra
   - Create recommendation
5. Broadcast `consolidation_suggested` hook

### Alternative Flows

**A1: MOQ met**
- No action, order proceeds

**A2: Multiple pending suggestions**
- Group suggestions for same supplier
- Evaluate if combined qty meets MOQ
- Recommend consolidation

### Related FR
FR-MC-001-001