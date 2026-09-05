# FR-MC-001-001: Apply MOQ Rules

## Version
1.0.0

## Author
KSF Development Team

## Created
2026-09-04

## Status
Approved

## Functional Requirement

## FR-MC-001-001: Apply MOQ Rules

### Description
Adjust suggested order quantities to meet manufacturer minimum order quantities.

### Input
- Suggested order quantity
- MOQ rule for supplier/item
- Order multiple (if any)

### Processing
```
if qty < moq:
    adjusted_qty = moq
elif order_multiple > 0:
    adjusted_qty = ceil(qty / order_multiple) * order_multiple
else:
    adjusted_qty = qty
```

### Output
- Adjusted quantity meeting MOQ
- Additional cost of MOQ rounding
- Days to sell extra inventory

### Business Rules
- MOQ rules have effective dates
- Inactive rules ignored
- MOQ can be per-supplier or per-item

### Acceptance Criteria
- [ ] Quantities below MOQ rounded up to MOQ
- [ ] Order multiples respected
- [ ] Additional cost calculated correctly
- [ ] Inactive rules ignored