# BR-MC-001: Manufacturer Consolidation Module

## Version
1.0.0

## Author
KSF Development Team

## Created
2026-09-04

## Status
Approved

## Business Requirement

Manage manufacturer minimum order quantities (MOQ) and consolidate suggested POs to meet supplier minimums. Prevents partial orders that sit waiting for MOQ completion.

### Problem Statement

Without consolidation:
1. POs created below MOQ sit unfilled
2. No visibility into cost of rounding up to MOQ
3. Manual intervention to add items to reach MOQ
4. Lost bulk discount opportunities

### Solution

- Maintain MOQ rules per supplier (and per item)
- Evaluate suggested POs against MOQ rules
- Generate recommendations with ROI analysis
- Suggest consolidation with other pending orders
- Broadcast `consolidation_suggested` hook

### Scope

**In Scope:**
- MOQ rule management (CRUD)
- Evaluate suggested POs against MOQ
- Calculate extra cost of MOQ rounding
- Estimate days-to-sell extra inventory
- Consolidate multiple suggestions into one PO

**Out of Scope:**
- Creating actual POs (SuggestedPO does this)
- Supplier bulk discount lookup
- Manufacturer lead time consideration

### Inter-Module Hooks

| Hook | Direction | Payload |
|------|-----------|---------|
| `nightly_recalc` | Receives | Cron trigger |
| `suggested_po_approved` | Receives | Approved suggestion |
| `stock_turnover_data` | Receives | Turnover metrics |
| `po_tracking_data` | Receives | Lead time data |
| `consolidation_suggested` | Broadcasts | Recommendations |