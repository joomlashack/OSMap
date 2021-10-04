-- ============================================================================
-- Rename the index to keep consistency
ALTER TABLE `#__osmap_sitemaps`
    DROP INDEX `default`;
ALTER TABLE `#__osmap_sitemaps`
    ADD INDEX `idx_default` (`is_default` ASC, `id` ASC);

-- ============================================================================
-- Add the column "format" to the item settings table
ALTER TABLE `#__osmap_items_settings`
    ADD `format` TINYINT(1) UNSIGNED DEFAULT NULL COMMENT 'Format of the setting: 1) Legacy Mode - UID Only; 2) Based on menu ID and UID';
