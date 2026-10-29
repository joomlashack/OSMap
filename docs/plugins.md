# Writing an OSMap Plugin

On its own, OSMap only lists menu items. To list the content behind a menu item, such as the products in a shop category or the posts in a blog, you need a plugin in the `osmap` group for that component. This page explains how to write one.

The shipped `plg_osmap_joomla` plugin (`src/extensions/plg_osmap_joomla/joomla.php`) is a complete reference implementation for `com_content`. `plg_osmap_demo` is a minimal skeleton.

## How plugins are found

For each menu item, OSMap reads the component from the link (`option=com_example`). It then checks every **enabled** plugin in the `osmap` folder:

1. It loads `plugins/osmap/<element>/<element>.php`.
2. It expects a class named `Plg` + `Osmap` + `ucfirst(<element>)`, for example `PlgOsmapExample` for element `example`.
3. It gets an instance by calling `getInstance()` if the class defines it. Otherwise it calls `new $class($dispatcher)`.
4. The plugin is used if `getComponentElement()` returns the menu item's option.

Matching plugins run in plugin `ordering`.

## Manifest

```xml
<?xml version="1.0" encoding="utf-8"?>
<extension type="plugin" group="osmap" method="upgrade">
    <name>PLG_OSMAP_EXAMPLE</name>
    <version>1.0.0</version>
    <files>
        <filename plugin="example">example.php</filename>
        <folder>language</folder>
    </files>
    <config>
        <fields name="params">
            <fieldset name="basic">
                <!-- plugin options, passed to prepareMenuItem()/getTree() as $params -->
            </fieldset>
        </fields>
    </config>
</extension>
```

## Class skeleton

```php
<?php

use Alledia\OSMap\Factory;
use Alledia\OSMap\Plugin\Base;
use Alledia\OSMap\Plugin\ContentInterface;
use Alledia\OSMap\Sitemap\Collector;
use Alledia\OSMap\Sitemap\Item;
use Joomla\Registry\Registry;

defined('_JEXEC') or die();

class PlgOsmapExample extends Base implements ContentInterface
{
    protected static $instance = null;

    public static function getInstance()
    {
        if (empty(static::$instance)) {
            $dispatcher       = Factory::getDispatcher(); // works on Joomla 3 and 4+
            static::$instance = new static($dispatcher);
        }

        return static::$instance;
    }

    public function getComponentElement()
    {
        return 'com_example';
    }

    public static function prepareMenuItem($node, $params)
    {
        // Optional: adjust the menu item itself before it is output
        return true;
    }

    public static function getTree($collector, $parent, $params)
    {
        // Optional: add child items below the menu item
    }
}
```

`Alledia\OSMap\Plugin\Base` extends Joomla's `CMSPlugin`, loads the OSMap bootstrap in its constructor, and provides `checkMemory()` and `getViewFromUrl()`. Implementing `ContentInterface` is recommended but not required. OSMap calls `prepareMenuItem()` and `getTree()` only when they exist, and accepts both static and instance methods.

## `prepareMenuItem(Item $node, Registry $params)`

This is called for each **menu item** that links to your component, before the item is checked for duplicates and output. Use it to describe the page the menu item points to:

- set `$node->uid` to a stable ID for that content, for example `'example.product.' . $id`. OSMap uses the UID to find duplicates and to save per-item settings, so the same content must get the same UID no matter which menu item or plugin produces it.
- set `$node->modified`, `$node->created` and `$node->publishUp`
- set `$node->images` (see [Images](#images))
- set `$node->expandible = true` if `getTree()` will add children
- set `$node->adapterName` if you provide an `ItemAdapter`

Return `false` to drop the menu item and its whole subtree. Return `true` otherwise.

## `getTree(Collector $collector, Item $parent, Registry $params)`

This is called after an internal, non-ignored menu item has been output. Add its children by passing nodes to the collector:

```php
public static function getTree($collector, $parent, $params)
{
    static::checkMemory();

    $view = static::getViewFromUrl($parent->link);
    if ($view !== 'category') {
        return;
    }

    $collector->changeLevel(1);

    foreach (static::loadProducts($parent) as $product) {
        $node = (object)[
            'id'         => $parent->id,                       // menu item id of the parent
            'uid'        => 'example.product.' . $product->id,
            'name'       => $product->title,
            'link'       => 'index.php?option=com_example&view=product&id=' . $product->id,
            'browserNav' => $parent->browserNav,
            'priority'   => $params->get('priority', $parent->priority),
            'changefreq' => $params->get('changefreq', $parent->changefreq),
            'modified'   => $product->modified,
            'created'    => $product->created,
            'publishUp'  => $product->publish_up,
            'parentIsVisibleForRobots' => $parent->visibleForRobots,
        ];

        // printNode() returns the callback's result: true if the item was output
        if ($collector->printNode($node)) {
            // optionally recurse into sub-items here, wrapping them in changeLevel(1)/changeLevel(-1)
        }
    }

    $collector->changeLevel(-1);
}
```

Things to get right:

- **Levels.** Wrap each group of children in `changeLevel(1)` and `changeLevel(-1)`, and keep the calls balanced. The HTML sitemap builds its tree from levels. Children of an unpublished item are hidden automatically.
- **Links.** Pass the non-SEF `index.php?option=…` link in `link`. OSMap turns it into an absolute SEF URL in `fullLink`. Use your component's route helper when it adds an `Itemid` or category path, as `RouteHelper::getArticleRoute()` does in the Joomla plugin.
- **UID.** Always set one. Without a UID, OSMap uses an MD5 of the URL, and saved settings are lost whenever the URL changes.
- **Access and publishing.** Filter your own queries by publish state, publish up/down dates, and `General::getAuthorisedViewLevels()`. OSMap only filters the menu items themselves.
- **Memory.** On large sites, call `static::checkMemory()` inside loops. It throws a readable error before PHP runs out of memory. Don't keep large result sets around; set them to `null` after use.

## Node fields

You can set any public property of `Alledia\OSMap\Sitemap\Item` on the node. These are the most useful:

| Field | Purpose |
|---|---|
| `uid` | Stable unique ID (see above) |
| `name` | Title, shown in the HTML sitemap and the news `<news:title>` |
| `link` | Non-SEF internal link, or an absolute external URL |
| `priority`, `changefreq` | Defaults for this item. An admin's saved overrides take precedence. |
| `modified` | Becomes `<lastmod>` |
| `created`, `publishUp` | `publishUp` decides whether the item appears in the news sitemap |
| `language` | Language code, used for `<news:language>` and language filtering |
| `keywords` | Comma-separated keywords for `<news:keywords>` |
| `images` | Array of image objects, used by the image sitemap |
| `visibleForXML`, `visibleForHTML` | Hide the item from one output format |
| `parentIsVisibleForRobots` | Pass `$parent->visibleForRobots` so robots `noindex` carries down to children |
| `browserNav` | Copy from the parent |
| `adapterName` | Short class name under `Alledia\OSMap\Sitemap\ItemAdapter\` |
| `expandible`, `subnodes` | Optional bookkeeping for nested children, for example page breaks |

## Images

Each entry in `$node->images` is an object:

```php
(object)[
    'src'     => 'https://example.com/images/photo.jpg', // absolute URL, required
    'title'   => 'Caption or alt text',                    // optional
    'license' => 'https://example.com/license',            // optional
]
```

The `imagesHelper` service can build this list for you:

```php
$helper = \Alledia\OSMap\Factory::getPimpleContainer()->imagesHelper;

$node->images = array_merge(
    $helper->getImagesFromText($html, $params->get('max_images', 1000)),
    $helper->getImagesFromParams($item) // reads Joomla-style image_intro / image_fulltext JSON in $item->images
);
```

## Page breaks

`Alledia\OSMap\Helper\General::getPagebreaks($text, $baseLink, $uid)` finds `{pagebreak}`-style breaks in the text and returns one child node per page. Each node's UID is derived from `$uid`. Output the children with `printNode()` inside `changeLevel(1)` and `changeLevel(-1)`.

## Hooking into OSMap itself

Plugins in the `osmap` group can also handle the events listed under [Plugin events](architecture.md#plugin-events), for example to replace collection completely with `osmapOnBeforeCollectItems` or to add admin toolbar buttons.

## Legacy Xmap plugins

OSMap still runs old Xmap-style plugins: element named after the option (such as `com_example`), a class named `xmap_com_example` or `osmap_com_example`, params passed as an array, and `getTree()` calling `$xmap->printNode()` and `$xmap->changeLevel()`. Write new plugins in the style described above.
