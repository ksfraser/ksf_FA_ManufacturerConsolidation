<!-- Repo-specific appendix to the shared AGENTS.md. Generic conventions live in AGENTS_ARCH.md (hardlinked). -->

# AGENTS.local.md — ksf_FA_ManufacturerConsolidation
## Purpose
Manage manufacturer MOQ rules and consolidate suggested POs to meet supplier minimums.

## Hook Communication
```
[suggested_po_approved] → ManufacturerConsolidation → consolidation_suggested → [SuggestedPO]
[stock_turnover_data] → ManufacturerConsolidation (receives)
```

## Dependencies
- ksf_common_db (DbConnectionInterface)
- ksf_FA_StockTurnover (stock_turnover_data hook)
- ksf_FA_PurchaseOrderTracking (po_tracking_data hook)

## Development Workflow
All development is done in the **devel tree** (`~/Documents/ksf_FA_ManufacturerConsolidation`). Do **not** edit files in the Infrastructure bind point directly.

### Workflow Steps
1. **Develop** in this repo (feature/fix branches)
2. **Test**: `composer install && ./vendor/bin/phpunit`
3. **Lint**: `php -l` on modified PHP files
4. **Commit** and **Push** to GitHub
5. **Deploy** to Infrastructure:
   ```
   rsync -av --exclude='.git' ~/Documents/ksf_FA_ManufacturerConsolidation/ ~/Documents/ksf_Infrastructure/fa_modules/ksf_FA_ManufacturerConsolidation/
   ```

### Infrastructure Bind Point
| Path | Purpose |
|------|---------|
| `~/Documents/ksf_FA_ManufacturerConsolidation` | Devel tree |
| `~/Documents/ksf_Infrastructure/fa_modules/ksf_FA_ManufacturerConsolidation` | Deployment target |