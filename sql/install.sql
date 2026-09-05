-- Manufacturer/Minimum Order Quantity rules
CREATE TABLE IF NOT EXISTS `0_ksf_supplier_moq_rules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `supplier_id` INT NOT NULL,
    `stock_id` VARCHAR(20) NULL COMMENT 'NULL = applies to all items from this supplier',
    `manufacturer_id` VARCHAR(50) NULL COMMENT 'Mfr part number/id for grouping',
    `min_order_qty` DECIMAL(15,4) NOT NULL DEFAULT 1,
    `order_multiple` DECIMAL(15,4) NULL COMMENT 'Order in multiples of this',
    `min_order_value` DECIMAL(15,2) NULL COMMENT 'Minimum $ value per order',
    `effective_from` DATE NOT NULL,
    `effective_to` DATE NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_supplier` (`supplier_id`),
    INDEX `idx_stock` (`stock_id`),
    INDEX `idx_manufacturer` (`manufacturer_id`),
    INDEX `idx_effective` (`effective_from`, `effective_to`)
) ENGINE=InnoDB;

-- Consolidation Groups (batches multiple suggested POs)
CREATE TABLE IF NOT EXISTS `0_ksf_consolidation_groups` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `group_code` VARCHAR(30) NOT NULL,
    `supplier_id` INT NOT NULL,
    `status` ENUM('pending', 'approved', 'converted_to_po', 'cancelled') NOT NULL DEFAULT 'pending',
    `total_lines` INT NOT NULL DEFAULT 0,
    `total_value` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `total_units` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `moq_gap` DECIMAL(15,4) NOT NULL DEFAULT 0 COMMENT 'Qty needed to reach MOQ',
    `moq_gap_value` DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT '$ value of gap items',
    `consolidation_reason` VARCHAR(255) NULL,
    `source_suggestions` TEXT NULL COMMENT 'JSON array of suggestion IDs',
    `notes` TEXT NULL,
    `approved_by` INT NULL,
    `approved_at` DATETIME NULL,
    `converted_to_po` VARCHAR(20) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_group_code` (`group_code`),
    INDEX `idx_supplier_status` (`supplier_id`, `status`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB;

-- Consolidation Line Items
CREATE TABLE IF NOT EXISTS `0_ksf_consolidation_lines` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `group_id` INT NOT NULL,
    `suggested_order_id` INT NULL COMMENT 'Reference to suggested_orders.id if applicable',
    `stock_id` VARCHAR(20) NOT NULL,
    `qty_suggested` DECIMAL(15,4) NOT NULL,
    `qty_to_order` DECIMAL(15,4) NOT NULL COMMENT 'After MOQ adjustment',
    `unit_cost` DECIMAL(15,4) NOT NULL DEFAULT 0,
    `line_value` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `moq_applied` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 if MOQ forced this qty',
    `source_type` ENUM('suggested', 'manual', 'consolidated') NOT NULL DEFAULT 'suggested',
    `expected_delivery_date` DATE NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_group` (`group_id`),
    INDEX `idx_stock` (`stock_id`)
) ENGINE=InnoDB;

-- Consolidation Recommendations (for display/review)
CREATE TABLE IF NOT EXISTS `0_ksf_consolidation_recommendations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `supplier_id` INT NOT NULL,
    `stock_id` VARCHAR(20) NOT NULL,
    `current_suggested_qty` DECIMAL(15,4) NOT NULL,
    `moq` DECIMAL(15,4) NOT NULL,
    `recommended_qty` DECIMAL(15,4) NOT NULL COMMENT 'Rounded up to MOQ/multiple',
    `additional_cost` DECIMAL(15,2) NOT NULL DEFAULT 0 COMMENT 'Cost of extra qty',
    `days_to_sell_extra` DECIMAL(10,1) NULL COMMENT 'How long to sell extra at current rate',
    `roi_impact` VARCHAR(50) NULL COMMENT 'positive/negative/neutral',
    `recommendation_type` ENUM('moq_rounding', 'consolidation', 'bulk_discount', 'lead_time') NOT NULL,
    `rationale` TEXT NULL,
    `status` ENUM('pending', 'approved', 'rejected', 'applied') NOT NULL DEFAULT 'pending',
    `applied_to_group_id` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_supplier` (`supplier_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_roi_impact` (`roi_impact`)
) ENGINE=InnoDB;