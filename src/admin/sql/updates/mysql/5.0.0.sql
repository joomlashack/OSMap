-- MySQL Workbench Synchronization
-- Generated: 2021-10-07 10:51
-- Model: OSMap
-- Version: 5.0.0
-- Project: Joomlashack Extensions
-- Author: Bill Tomczak

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='';

ALTER TABLE `#__osmap_sitemaps` ADD INDEX `idx_default` (`is_default` ASC, `id` ASC);
ALTER TABLE `#__osmap_sitemaps` DROP INDEX `default_idx`;

ALTER TABLE `#__osmap_sitemap_menus` DROP FOREIGN KEY `fk_sitemaps_menus`;
ALTER TABLE `#__osmap_sitemap_menus` ADD INDEX `idx_menutype_id` (`menutype_id` ASC);
ALTER TABLE `#__osmap_sitemap_menus` ADD INDEX `idx_sitemaps_id` (`sitemap_id` ASC);
ALTER TABLE `#__osmap_sitemap_menus` DROP INDEX `idx_sitemap_menus`;
ALTER TABLE `#__osmap_sitemap_menus` DROP INDEX `idx_ordering`;

ALTER TABLE `#__osmap_items_settings` MODIFY`format` TINYINT(1) UNSIGNED NULL DEFAULT '2' COMMENT 'Format of the setting: 1) Legacy Mode - UID Only; 2) Based on menu ID and UID';
ALTER TABLE `#__osmap_items_settings` ADD INDEX `idx_sitemap_id` (`sitemap_id` ASC);

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
