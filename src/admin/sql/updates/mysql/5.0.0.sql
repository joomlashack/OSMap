-- MySQL Workbench Synchronization
-- Generated: 2021-10-06 09:50
-- Model: OSMap
-- Version: 5.0.0
-- Project: Joomlashack Extensions
-- Author: Bill Tomczak

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='';

ALTER TABLE `#__osmap_sitemaps`
    ADD INDEX `idx_default` (`is_default` ASC, `id` ASC),
    DROP INDEX `default_idx` ;

ALTER TABLE `#__osmap_sitemap_menus`
    ADD INDEX `idx_menutype_id` (`menutype_id` ASC),
    ADD INDEX `idx_sitemaps_id` (`sitemap_id` ASC),
    DROP INDEX `ordering_idx` ,
    DROP INDEX `fk_sitemaps_idx` ;

ALTER TABLE `#__osmap_items_settings`
    MODIFY `settings_hash` char(32) NOT NULL DEFAULT '';

ALTER TABLE `#__osmap_items_settings`
    ADD INDEX `idx_sitemap_id` (`sitemap_id` ASC);

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
