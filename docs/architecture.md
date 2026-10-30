# Architecture

OSMap builds sitemaps from Joomla menus. You assign one or more menus to a sitemap. OSMap walks each menu's items and asks `osmap` plugins to expand items that point to supported components, for example a category menu item into its articles. Each resulting item is passed to a view callback, which renders XML, HTML or an admin editing list.

## Bootstrap

Every entry point requires `administrator/components/com_osmap/include.php`. That includes the site and admin `osmap.php` files, the `Plugin\Base` constructor and the installer. The bootstrap:

1. loads the Joomlashack framework from `libraries/allediaframework/include.php`. If the framework is missing, it stops with an admin error.
2. defines `OSMAP_LOADED`, `OSMAP_ADMIN_PATH`, `OSMAP_SITE_PATH` and `OSMAP_LIBRARY_PATH`.
3. registers autoloading for `Alledia\…` from `admin/library/Alledia` and for `Pimple`.
4. imports the `osmap` plugin group, loads language files, and adds Joomla include paths for tables, forms, fields and HTML helpers.
5. registers the `com_osmap` log category.

Both clients then dispatch through legacy Joomla MVC: `BaseController::getInstance('OSMap')`. The site's default view is `xml`.

## Services

`Alledia\OSMap\Factory` extends `Alledia\Framework\Factory`, which smooths over differences between Joomla versions. It adds:

- `getPimpleContainer()`: a singleton `Alledia\OSMap\Container`, which is a Pimple container. The `Services` provider fills it with:

  | Key            | Service                                   |
  |----------------|-------------------------------------------|
  | `app`          | Current application                       |
  | `db`           | Database driver                           |
  | `input`        | Request input                             |
  | `user`         | Current user                              |
  | `language`     | Current language                          |
  | `router`       | `Alledia\OSMap\Router` (URL helpers)      |
  | `uri`          | `Joomla\CMS\Uri\Uri`                      |
  | `profiler`     | Framework profiler                        |
  | `imagesHelper` | `Alledia\OSMap\Helper\Images`             |

  Services can be read as properties (`$container->db`) or with getters (`$container->getDb()`).

- `getSitemap($id, $type)`: returns a `Sitemap\Standard`, `Sitemap\Images` or `Sitemap\News`. All three share the `SitemapInterface::traverse()` contract.
- `getTable($name)`: returns an `OSMapTable*` instance.

`Alledia\OSMap\Router` contains the URL handling: routing to absolute SEF URLs, telling internal from external links, converting relative URIs, sanitizing, removing `#fragment`s, and hashing URLs.

## Data model

| Table                     | Purpose |
|---------------------------|---------|
| `#__osmap_sitemaps`       | One row per sitemap: name, params, `is_default`, `published`, and a cached `links_count`. |
| `#__osmap_sitemap_menus`  | Menus assigned to a sitemap, with the default `changefreq` and `priority` for that menu's items and their order. |
| `#__osmap_items_settings` | Per-item overrides of `published`, `changefreq` and `priority`, keyed by `(sitemap_id, uid, settings_hash)`. Rows with `format = 2` are current. Rows with `format IS NULL` are legacy, keyed by UID only, from versions before 4.2.1. |

## Sitemap generation

```
View (xml / html / adminsitemapitems)
  └─ $sitemap->traverse($callback)
       ├─ trigger osmapOnBeforeCollectItems($sitemap, $callback)   ← a plugin returning true stops here
       └─ Collector::fetch($callback)
            for each assigned menu (in order):
              for each published, accessible menu item (in tree order):
                ├─ submitItemToCallback($item, $callback, prepare = true)
                │    ├─ new Item(...)                        compute link, fullLink, settingsHash, uid
                │    ├─ plugins → prepareMenuItem($item)     may set uid, dates, images, adapter; false ⇒ ignore
                │    ├─ adapter → checkVisibilityForRobots()
                │    ├─ apply custom settings / menu defaults
                │    ├─ inherit "unpublished" from parent level
                │    ├─ flag duplicate UID / duplicate URL
                │    └─ $callback($item)
                └─ if internal and not ignored:
                     plugins → getTree($collector, $item, $params)
                        └─ $collector->printNode($node) for each child (same pipeline, no prepare step)
            update links_count (when requested)
```

### Collector

`Sitemap\Collector` does the work:

- **Menu items.** It selects items from `#__menu` for each menu in `#__osmap_sitemap_menus`. It filters by the user's view access levels and, on a multilingual site, by language. With the **Ignore hidden menus** option, it also skips items set to `menu_show = 0`.
- **Levels.** `currentLevel` follows the menu tree. Plugins call `changeLevel(+1/-1)` around the children they add. The HTML views use levels to rebuild the tree.
- **Plugin dispatch.** `Helper\General::getPluginsForComponent($option)` finds the enabled `osmap` plugins (and legacy `xmap` plugins) that handle the item's component, then calls `prepareMenuItem()` and `getTree()` on each. `General::callUserFunc()` calls these as static or instance methods, depending on how the plugin declared them.
- **Settings.** An item's settings key is `uid:settingsHash`. If no current override exists, the collector tries the legacy UID-only settings. If there is still nothing, a menu item takes its menu's defaults, and a plugin-generated item keeps whatever the plugin set.
- **Unpublished parents.** When an item is unpublished or ignored, every deeper item is unpublished until the tree returns to that item's level or above. The item gets the admin note `COM_OSMAP_ADMIN_NOTE_PARENT_UNPUBLISHED`.
- **Duplicates.** A second item with a UID or URL that has already been registered gets `duplicate = true`. With **Ignore duplicated UIDs** on (the default), duplicates are excluded from output and from the link count.

### Item

`Sitemap\Item` (a `CMSObject`) is built from the raw menu row or plugin node. Its constructor:

- converts dates to UTC (`created`, `modified`, and `publishUp`, which falls back to `created` or `modified`)
- resolves alias menu items to their target
- extracts `component` from `option=com_xxx`
- builds `fullLink`. The home item gets the site root, or the language home page. Separators and headings get no link. External URLs are kept as they are. Internal links are routed to absolute SEF URLs.
- removes the `#fragment` from `fullLink` (`rawLink` keeps it)
- computes `settingsHash = hash(fullLink + current menu item id)`, so the same page can carry different settings under different menu items
- sets `uid` to `md5(fullLink)` when no plugin has assigned one. External links are prefixed with `external.`.

Callbacks use these flags to decide what to output:

| Property | Meaning |
|---|---|
| `ignore` | Excluded by a plugin. Children are skipped too. |
| `published` | Admin-controlled via item settings |
| `duplicate` | UID or URL already registered |
| `visibleForRobots` | Result of the item adapter's check. Always true in Free. |
| `parentIsVisibleForRobots` | Set by plugins on child nodes |
| `visibleForXML` / `visibleForHTML` | Per-format visibility set by plugins |
| `isInternal` | Points to this site |
| `level` | Depth in the tree |
| `adminNotes` | Messages shown in the admin items list |

### Item adapters

`Item::setAdapter()` creates `Alledia\OSMap\Sitemap\ItemAdapter\<adapterName>`. If that class doesn't exist, it falls back to `Generic`. Plugins choose an adapter by setting `adapterName`; the Joomla plugin uses `JoomlaArticle` and `JoomlaCategory`. Free only ships `Generic`, whose robots check always returns `true`. Pro adds adapters that read robots metadata.

## Views

### Site

| View | URL | Output |
|---|---|---|
| `xml` | `index.php?option=com_osmap&view=xml&id=N&format=xml` | Standard sitemap (`default_standard.php`) |
| `xml` + `&images=1` | … | Google image sitemap (`default_images.php`). Uses `$item->images`. |
| `xml` + `&news=1` | … | Google news sitemap (`default_news.php`). Includes items whose `publishUp` is within the last 2 days, up to 1000 items. |
| `xsl` | `view=xsl&format=xsl&layout=standard\|images\|news` | XSL stylesheet that the XML output references when **Add styling** is on |
| `html` | `index.php?option=com_osmap&view=html&id=N` | Human-readable nested list |
| `adminsitemapitems` | `view=adminsitemapitems&tmpl=component&id=N` | The item list in the admin sitemap item editor |

The `xml` view sends a request that doesn't use the XML document format to the same URL with `format=xml`. The XML and HTML views are usually reached through Joomla menu items. Their layout options are defined in `site/views/*/tmpl/default.xml`.

`adminsitemapitems` is a frontend view because links have to be routed by the site router. The admin `sitemapitems` view loads it over AJAX (`media/js/sitemapitems.js`). The editor posts changes back to the admin `sitemapitems` controller, which writes them to `#__osmap_items_settings`.

The HTML views extend `Alledia\OSMap\View\Site\AbstractList`. It collects items into a tree using `level`, groups them under their menu, and renders them.

### Administrator

| View | Purpose |
|---|---|
| `sitemaps` | List of sitemaps, with preview links for XML, HTML, news and images in each language |
| `sitemap` | Edit a sitemap: name, assigned menus, menu priority and changefreq, default flag |
| `sitemapitems` | Per-item publish, priority and changefreq editor |

Admin controllers extend `Alledia\OSMap\Controller\Admin` or `\Form`. Views extend `Alledia\OSMap\View\Admin\AbstractList` or `\AbstractForm`. These classes add OSMap's toolbar, submenu and plugin events on top of the ShackFramework classes.

## Plugin events

Besides the content-plugin methods (see [plugins.md](plugins.md)), OSMap triggers these events on the `osmap` plugin group:

| Event | Arguments | Notes |
|---|---|---|
| `osmapOnBeforeCollectItems` | `SitemapInterface $sitemap, callable $callback` | A handler that returns `true` stops the normal collection. |
| `osmapOnBeforeExecuteTask` | `string $controller, string $task` | A handler that returns `'exit'` stops an admin task. |
| `osmapOnAfterExecuteTask` | `string $controller, string $task, &$result` | |
| `osmapOnAfterSetToolBar` | `string $viewName` | Adds buttons to admin toolbars. |
| `onOSMapAddAdminSubmenu` | `array &$submenus` | Adds or changes admin submenu entries. |

## Installer

`src/script.installer.php` defines `com_osmapInstallerScript`, which extends `Alledia\Installer\OSMap\Free\AbstractScript`, which extends ShackInstaller's `AbstractScript`. ShackInstaller installs the related extensions and removes obsolete ones, as declared in the manifest. OSMap's post-flight step adds:

- a "Default Sitemap", created when no sitemaps exist. Every site menu is assigned to it, with priority 0.5 and changefreq `weekly`.
- migration from OSMap versions before 4 (the legacy `#__osmap_sitemap` tables), including legacy UID conversion. Xmap data migration was removed in v5.1.1.
- schema checks and repairs (`checkDatabaseSchema()`)
- adding `&format=xml` to existing XML sitemap menu items, and deleting OSMap language files from the core `language/` folders

## Legacy compatibility

- `XmapHelper` and `OSMapHelper` (in `site/helpers/`) are aliases of `Alledia\OSMap\Helper\General` for old plugins.
- Plugins in the `xmap` folder still run. A plugin counts as legacy when its element matches the component option, as in `com_content`. Its params are passed as an array, and its class is `xmap_<option>`.
- `Collector::printNode()` and `changeLevel()` keep the Xmap plugin API.
