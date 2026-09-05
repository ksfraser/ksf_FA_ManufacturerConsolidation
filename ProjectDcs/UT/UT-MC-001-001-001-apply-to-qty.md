# UT-MC-001-001-001: MoqRuleDTO Apply To Qty

## Version
1.0.0

## Author
KSF Development Team

## Created
2026-09-04

## Status
Approved

## Unit Test

## UT-MC-001-001-001: MoqRuleDTO Apply To Qty

### Class Under Test
`Ksfraser\FrontAccounting\ManufacturerConsolidation\MoqRuleDTO`

### Method
`applyToQty()`

### Test Case
Verify qty adjustment when below MOQ.

### Test Data
```php
moq = 50.0
suggested_qty = 25.0
```

### Expected Result
- Adjusted qty = 50.0 (MOQ applied)

### Related FR
FR-MC-001-001