# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

OSMap (Free) is a Joomla sitemap component (`com_osmap`), descended from Xmap. It supports Joomla 3.9+ and Joomla 4/5 from a single codebase, with PHP 7.4 as the minimum. The `<alledia>` block in `src/osmap.xml` is the authoritative source for these values. The requirements in README.md are out of date.

A separate OSMap Pro repository extends this code. Pro is detected at runtime when `admin/library/Alledia/OSMap/Services/Pro.php` exists (see `OSMAP_LICENSE` in `src/admin/include.php`). Free code checks for Pro classes such as `GenericPro` with `class_exists()`. Keep these hooks intact.

Developer documentation lives in `docs/`: `development.md` (build, conventions, releases), `architecture.md` (generation pipeline, data model, events) and `plugins.md` (writing `osmap` content plugins). Update these files when you change the behavior they describe.

## Build

There is no test suite, linter config, or composer dependency install. `composer.json` only holds metadata. Packages are built with **Phing** and the external **ShackBuilder** tool:

```sh
cp build.dist.properties build.properties   # then set builder.path and the project.*.path entries
phing                                         # builds an installable package into packages/
```

`build.xml` imports `${builder.path}/src/build.xml`. ShackBuilder then pulls in the related extensions listed in the manifest's `<relatedExtensions>`/`<include>` (ShackFramework library, ShackInstaller, ShackDefaultFiles, ShackExtensionSupport) from the local paths in `build.properties`. It also minifies the files listed in `<minify>`. When you change `src/media/js/sitemapitems.js` or any CSS file, the `.min.*` counterpart is committed alongside it.

Code follows PSR-12 style (note the `phpcs:disable` annotations). Every PHP file starts with the GPL license header and `defined('_JEXEC') or die();`.

## Releases and branches

`main` holds releases ("Release vX.Y.Z" commits). `development` is the integration branch, and pull requests go there. A release bumps `<version>` and `<creationDate>` in `src/osmap.xml`. A schema change adds a file to `src/admin/sql/updates/mysql/<version>.sql`.

## Architecture

### Bootstrap
Every entry point (site `osmap.php`, admin `osmap.php`, plugins) requires `src/admin/include.php`. That file:
- loads the Joomlashack framework from `libraries/allediaframework`, a hard runtime dependency (`Alledia\Framework\*`)
- defines the `OSMAP_*` path constants
- registers autoloading for `Alledia\` from `admin/library/Alledia` (the OSMap code) and `Pimple`
- imports the `osmap` plugin group and adds Joomla table, form, and HTML include paths

The code uses legacy, non-namespaced Joomla MVC: `controller.php`, `controllers/`, `models/`, `views/`, and `tables/` with `OSMap*` class prefixes. The OSMap base classes for controllers and views live in `admin/library/Alledia/OSMap/{Controller,View}` and extend ShackFramework classes.

### Service container
`Alledia\OSMap\Factory` extends the framework Factory. `Factory::getPimpleContainer()` builds a Pimple `Container` and registers `Services\Free` (or `Services\Pro`), which provides `app`, `db`, `input`, `user`, `language`, `router`, `uri`, `profiler`, and `imagesHelper`. The `Container` class adds magic access (`$container->db`, `$container->getDb()`). `Factory::getSitemap($id, $type)` returns `Sitemap\Standard`, `Images`, or `News`.

### Sitemap generation
1. A `Sitemap\*` object's `traverse($callback)` creates a `Sitemap\Collector`.
2. `Collector::fetch()` reads the menus assigned to the sitemap (`#__osmap_sitemap_menus`), loads per-item overrides (`#__osmap_items_settings`), and walks the menu items. It wraps each one in a `Sitemap\Item`, which picks an `ItemAdapter` (`Generic`, or `GenericPro` when available).
3. For each internal menu item, the collector finds plugins in the `osmap` group that support the item's component (`General::getPluginsForComponent`). It calls their static `prepareMenuItem()` and `getTree()` through `General::callUserFunc`. Plugins expand a menu item into child content and push nodes back through `$collector->submitItemToCallback()`. That method deduplicates by UID, applies item settings, and invokes the callback.
4. Callbacks in the views render the output. The site views are `xml` (standard, images, and news templates), `html`, and `xsl` (stylesheets). The admin `sitemapitems` view and the site `adminsitemapitems` view (which `media/js/sitemapitems.js` fetches from the frontend so that links resolve with the site router) edit per-item priority, changefreq, and published state.

### Content plugins
Plugins live in `src/extensions/plg_osmap_*`. They extend `Alledia\OSMap\Plugin\Base` and implement `Plugin\ContentInterface` (`getInstance`, `getComponentElement`, `prepareMenuItem`, `getTree`). `plg_osmap_joomla` handles `com_content` (categories, articles, pagebreaks, robots/noindex handling) and is installed with the component as a related extension. `plg_osmap_demo` is a reference/demo plugin. The manifest's `<obsolete>` list removes legacy per-component plugins (K2, VirtueMart, etc.) on upgrade.

### Joomla 3 vs 4+
Templates come in pairs: `*.j3.php` for Joomla 3 and the plain `*.php` for Joomla 4+. Form field layouts use `*_j3.php` instead. ShackFramework picks the Joomla 3 file when it exists (`TraitAllediaView::getVersionedLayoutName()`, `Form\Field\TraitLayouts`). A few code paths branch on `Version::MAJOR_VERSION`. When you change a layout, update both variants.

The ShackFramework source (`Alledia\Framework\*`) is not in this repo. PhpStorm links it as an include path at `../../Builders/AllediaFramework`, next to ShackBuilder, ShackInstaller, ShackDefaultFiles and ShackExtensionSupport. Read it there to see what the OSMap base classes inherit.

### Installer
`src/script.installer.php` → `admin/library/Installer/OSMap/Free/AbstractScript.php`, which extends ShackInstaller's `AbstractScript`. It handles post-install steps: creating a default sitemap, migrating legacy Xmap/OSMap data, checking the database schema, and cleanup.
