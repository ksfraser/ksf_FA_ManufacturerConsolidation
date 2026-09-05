# UT-MC-001-001-002: MoqRuleDTO Order Multiple

## Version
1.0.0

## Author
KSF Development Team

## Created
2026-09-04

## Status
Approved

## Unit Test

## UT-MC-001-001-002: MoqRuleDTO Order Multiple

### Class Under Test
`Ksfraser\FrontAccounting\ManufacturerConsolidation\MoqRuleDTO`

### Method
`applyToQty()`

### Test Case
Verify qty adjustment with order multiple.

### Test Data
```php
moq = 10.0
order_multiple = 5.0
suggested_qty = 12.0
```

### Expected Result
- Adjusted qty = 15.0 (rounded up to multiple of 5)

### Related FR
FR-MC-001-001