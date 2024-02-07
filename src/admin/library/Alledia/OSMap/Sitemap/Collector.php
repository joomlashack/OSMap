<?php
/**
 * @package   OSMap
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2007-2014 XMap - Joomla! Vargas - Guillermo Vargas. All rights reserved.
 * @copyright 2016-2024 Joomlashack.com. All rights reserved.
 * @license   https://www.gnu.org/licenses/gpl.html GNU/GPL
 *
 * This file is part of OSMap.
 *
 * OSMap is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * OSMap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OSMap.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Alledia\OSMap\Sitemap;

use Alledia\OSMap\Factory;
use Alledia\OSMap\Helper\General;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;

defined('_JEXEC') or die();

/**
 * Sitemap items collector
 */
class Collector
{
    /**
     * @var SitemapInterface
     */
    protected $sitemap;

    /**
     * @var array
     */
    protected $uidList = [];

    /**
     * @var array
     */
    protected $urlHashList = [];

    /**
     * Callback used to trigger the desired action while fetching items.
     * This is only used in the legacy method printNode, which is called by
     * the osmap plugins to process the additional items.
     *
     * @var callable
     */
    protected $printNodeCallback;

    /**
     * The current view: xml or html. Kept for backward compatibility with
     * the legacy plugins. It is always XML since the collector is generic now
     * and needs to have the information about the item's level even for the
     * XML view in the Pro version
     *
     * @var string
     */
    public $view = 'xml';

    /**
     * Legacy property used by some plugins. True if we are collecting news.
     *
     * @var string
     *
     * @deprecated
     */
    public $isNews = false;

    /**
     * The items counter.
     *
     * @var int
     */
    protected $counter = 0;

    /**
     * The custom settings for items
     *
     * @var array
     */
    protected $itemsSettings = null;

    /**
     * The legacy custom settings for items. Which will be upgraded
     *
     * @var array
     */
    protected $legacyItemsSettings = null;

    /**
     * If false, say that any next sub-level should be unpublished
     *
     * @var mixed
     */
    protected $unpublishLevel = false;

    /**
     * @var array
     */
    protected $tmpItemDefaultSettings = [
        'changefreq' => 'weekly',
        'priority'   => '0.5'
    ];

    /**
     * The current items level
     *
     * @var int
     */
    protected $currentLevel = 0;

    /**
     * The reference for the instance of the current menu for item and its
     * subitems.
     *
     * @var object
     */
    protected $currentMenu;

    /**
     * The ID of the current menu item for nodes
     *
     * @var int
     */
    protected $currentMenuItemId = null;

    /**
     * The component's params
     *
     * @var Registry
     */
    public $params;

    /**
     * Collector constructor.
     *
     * @param SitemapInterface $sitemap
     *
     * @return void
     */
    public function __construct(SitemapInterface $sitemap)
    {
        $this->sitemap = $sitemap;
        $this->params  = ComponentHelper::getParams('com_osmap');

        /*
         * Try to detect the current view. This is just for backward compatibility
         * for legacy plugins. New plugins doesn't need to know what is the view.
         * They always calculate the visibility for both views and the view is
         * the one who decides to whow or not. If not equals HTML, is always XML.
         */
        $inputView = Factory::getPimpleContainer()->input->get('view', 'xml');
        if ($inputView === 'html') {
            $this->view = 'html';
        }
    }

    /**
     * Collects sitemap items based on the selected menus. This is the main
     * method of this class. For each found item, it will call the given
     * callback, so it can manipulate the data in many ways. It returns the
     * total of found items.
     *
     * @param callable $callback
     *
     * @return int
     */
    public function fetch(callable $callback): int
    {
        $menus = $this->getSitemapMenus();

        $this->counter = 0;
        if ($menus) {
            $this->getLegacyItemsSettings();
            $this->getItemsSettings();

            foreach ($menus as $menu) {
                $this->currentMenu = &$menu;

                $items = $this->getMenuItems($menu);
                foreach ($items as $item) {
                    if ($this->itemIsBlackListed($item)) {
                        $item = null;

                        continue;
                    }

                    // Store the current menu item id. Added to use it while defining the node's settings hash, so same
                    // items, but from different menus can have individual settings
                    $this->currentMenuItemId = $item['id'];

                    // Set the menu item UID. The UID can be changed by 3rd party plugins, according to the content
                    $item['uid'] = 'menuitem.' . $item['id'];

                    // Store the menu settings to use in the submitItemToCallback called by callbacks
                    $this->tmpItemDefaultSettings['changefreq'] = $menu->changefreq;
                    $this->tmpItemDefaultSettings['priority']   = $menu->priority;

                    // Check the level of menu
                    $level = (int)$item['level'] - 1;
                    if ($level !== $this->currentLevel) {
                        $this->changeLevel($level - $this->currentLevel);
                    }

                    // Submit the item and prepare it calling the plugins
                    $this->submitItemToCallback($item, $callback, true);

                    // Internal links can trigger plugins to grab more items
                    // The child items are not displayed if the parent item is ignored
                    if ($item->isInternal && !$item->ignore) {
                        // Call the plugin to get additional items related to it
                        $this->callPluginsGetItemTree($item, $callback);
                    }

                    // Make sure the memory is cleaned up
                    $item = null;
                }
                $items = [];
                unset($items);
            }

            $menu = null;
        }

        $this->currentMenu            = null;
        $this->tmpItemDefaultSettings = [];

        return $this->counter;
    }

    /**
     * Submit the item to the callback, checking duplicity and incrementing
     * the counter. It can receive an array or object and returns true or false
     * according to the result of the callback.
     *
     * @param array|object $item
     * @param callable     $callback
     * @param bool         $prepareItem
     *
     * @return bool
     */
    public function submitItemToCallback(&$item, callable $callback, bool $prepareItem = false): bool
    {
        $currentMenuItemId = $this->getCurrentMenuItemId();

        $item = (object)$item;

        // Add the menu information
        $item->menuItemId    = $this->currentMenu->id;
        $item->menuItemTitle = $this->currentMenu->name;
        $item->menuItemType  = $this->currentMenu->menutype;


        // Converts to an Item instance, setting internal attributes
        $item = new Item($item, $currentMenuItemId);

        if ($prepareItem) {
            // Call the plugins to prepare the item
            $this->callPluginsPreparingTheItem($item);
        }

        // Make sure to have the correct date format (UTC)
        $item->setModificationDate();

        $item->setAdapter();
        $item->visibleForRobots = $item->adapter->checkVisibilityForRobots();

        // Set the current level to the item
        $item->level = $this->currentLevel;

        $this->setItemCustomSettings($item);
        $this->checkParentIsUnpublished($item);
        $this->checkDuplicatedUIDToIgnore($item);

        // Verify if the item can be displayed to count as unique for the XML sitemap
        if (
            !$item->ignore
            && $item->published
            && $item->visibleForRobots
            && (!$item->duplicate || !$this->params->get('ignore_duplicated_uids', 1))
        ) {
            // Check if the URL is not duplicated (specially for the XML sitemap)
            $this->checkDuplicatedURLToIgnore($item);

            if (!$item->duplicate || !$this->params->get('ignore_duplicated_uids', 1)) {
                ++$this->counter;
            }
        }

        return (bool)call_user_func_array($callback, [&$item]);
    }

    /**
     * Gets the list of selected menus for the sitemap.
     * It returns a list of objects with the attributes:
     *  - name
     *  - menutype
     *  - priority
     *  - changefrq
     *  - ordering
     *
     * @return object[];
     */
    protected function getSitemapMenus(): array
    {
        $db = Factory::getDbo();

        $query = $db->getQuery(true)
            ->select([
                'mt.id',
                'mt.title AS ' . $db->quoteName('name'),
                'mt.menutype',
                'osm.changefreq',
                'osm.priority',
                'osm.ordering'
            ])
            ->from('#__osmap_sitemap_menus AS osm')
            ->join('LEFT', '#__menu_types AS mt ON (osm.menutype_id = mt.id)')
            ->where('osm.sitemap_id = ' . $db->quote($this->sitemap->id))
            ->order('osm.ordering');

        return $db->setQuery($query)->loadObjectList('menutype');
    }

    /**
     * Get the menu items as a tree
     *
     * @param object $menu
     *
     * @return array
     */
    protected function getMenuItems(object $menu): array
    {
        $container = Factory::getPimpleContainer();
        $db        = $container->db;
        $app       = $container->app;
        $lang      = $container->language;

        $query = $db->getQuery(true)
            ->select([
                'm.id',
                'm.title AS ' . $db->quoteName('name'),
                'm.alias',
                'm.path',
                'm.level',
                'm.type',
                'm.home',
                'm.params',
                'm.parent_id',
                'm.browserNav',
                'm.link',
                '1 AS ' . $db->quoteName('isMenuItem'), // Say that the menu came from a menu
                '0 AS ' . $db->quoteName('ignore')     // Flag that allows child classes choose to ignore items
            ])
            ->from('#__menu AS m')
            ->join('INNER', '#__menu AS p ON (p.lft = 0)')
            ->where([
                'm.menutype = ' . $db->quote($menu->menutype),
                'm.published = 1',
                sprintf('m.access IN (%s)', General::getAuthorisedViewLevels()),
                'm.lft > p.lft',
                'm.lft < p.rgt'
            ])
            ->order('m.lft');

        if ($app->isClient('site')) {
            if ($app->getLanguageFilter()) {
                $languageTags = array_map([$db, 'quote'], [$lang->getTag(), '*']);

                $query->where(sprintf('m.language IN (%s)', join(',', $languageTags)));
            }
        }

        $items = $db->setQuery($query)->loadAssocList();

        if ($this->params->get('ignore_hidden_menus', false)) {
            $items = array_filter(
                $items,
                function ($menu) {
                    $params = json_decode($menu['params']);
                    if (isset($params->menu_show) && $params->menu_show == 0) {
                        return false;
                    }

                    return true;
                }
            );
        }

        return $items;
    }

    /**
     * Checks if the item's uid was already registered. If positive, set the
     * item to be ignored and return true. If negative, register the item and
     * return false.
     *
     * @param Item $item
     *
     * @return bool
     */
    protected function checkDuplicatedUIDToIgnore(Item $item): bool
    {
        // If is already set, interrupt the flux and ignore the item
        if (isset($this->uidList[$item->uid])) {
            $item->duplicate = true;

            if ($this->params->get('ignore_duplicated_uids', 1)) {
                $item->addAdminNote('COM_OSMAP_ADMIN_NOTE_DUPLICATED_IGNORED');
            } else {
                $item->addAdminNote('COM_OSMAP_ADMIN_NOTE_DUPLICATED');
            }

            return true;
        }

        // Not set and published, so let's register
        if ($item->published && $item->visibleForRobots && !$item->ignore) {
            $this->uidList[$item->uid] = 1;
        }

        return false;
    }

    /**
     * Checks if the item's full link was already registered. If positive,
     * set the item to be ignored and return true. If negative, register the item and return false
     *
     * @param Item $item
     *
     * @return bool
     */
    protected function checkDuplicatedURLToIgnore(Item $item): bool
    {
        if (!empty($item->fullLink)) {
            $container = Factory::getPimpleContainer();

            // We need to make sure to have a URL free of hash chars
            $url  = $container->router->removeHashFromURL($item->fullLink);
            $hash = $container->router->createUrlHash($url);

            if (isset($this->urlHashList[$hash])) {
                $item->duplicate = true;
                $item->addAdminNote('COM_OSMAP_ADMIN_NOTE_DUPLICATED_URL_IGNORED');

                return true;
            }

            // Not set and published, so let's register
            if ($item->published && $item->visibleForRobots && !$item->ignore) {
                $this->urlHashList[$hash] = 1;
            }
        }

        return false;
    }

    /**
     * Calls the respective OSMap and XMap plugin, according to the item's
     * component/option. If the plugin's method returns false, it will set
     * the item's ignore attribute to true.
     *
     * @param Item $item
     *
     * @return void
     */
    protected function callPluginsPreparingTheItem(Item $item)
    {
        $plugins = General::getPluginsForComponent($item->component);

        foreach ($plugins as $plugin) {
            $className = '\\' . $plugin->className;

            if (method_exists($className, 'prepareMenuItem')) {
                if ($plugin->isLegacy) {
                    $params = $plugin->params->toArray();
                } else {
                    $params =& $plugin->params;
                }

                $arguments = [
                    &$item,
                    &$params
                ];

                // If a legacy plugin doesn't specify this method as static, fix the plugin to avoid warnings
                $result = General::callUserFunc(
                    $className,
                    $plugin->instance,
                    'prepareMenuItem',
                    $arguments
                );

                // If a plugin doesn't return true we ignore the item and break
                if ($result === false) {
                    $item->set('ignore', true);

                    break;
                }
            }

            $plugin = null;
        }
    }

    /**
     * Calls the respective OSMap and XMap plugin, according to the item's
     * component/option. Get additional items and send to the callback.
     *
     * @param Item     $item
     * @param callable $callback
     *
     * @return void
     */
    protected function callPluginsGetItemTree(Item $item, callable $callback)
    {
        $this->printNodeCallback = $callback;

        // Call the OSMap and XMap legacy plugins
        $plugins = General::getPluginsForComponent($item->component);

        foreach ($plugins as $plugin) {
            $className = '\\' . $plugin->className;
            if (method_exists($className, 'getTree')) {
                if ($plugin->isLegacy) {
                    $params = $plugin->params->toArray();
                } else {
                    $params = $plugin->params;
                }

                $arguments = [
                    &$this,
                    &$item,
                    &$params
                ];

                General::callUserFunc(
                    $className,
                    $plugin->instance,
                    'getTree',
                    $arguments
                );
            }
        }
    }

    /**
     * Returns true if the link of the item is in the blacklist array.
     *
     * @param array $item
     *
     * @return bool
     */
    protected function itemIsBlackListed(array $item): bool
    {
        $blackList = [
            'administrator' => 1
        ];

        $link = $item['link'];

        return isset($blackList[$link]);
    }

    /**
     * This method is used for backward compatibility. The plugins will call
     * it. In the legacy XMap, its behavior depends on the sitemap view type,
     * only changing the level in the HTML view. OSMap will always consider the
     * level of the item, even for XML view. XML will just ignore that.
     *
     * @param int $step
     *
     * @return void
     */
    public function changeLevel(int $step)
    {
        $this->currentLevel += $step;
    }

    /**
     * Method called by legacy plugins, which will pass the new item to the
     * callback. Returns the result of the callback converted to boolean.
     *
     * @param array|object $node
     *
     * @return bool
     */
    public function printNode($node): bool
    {
        return $this->submitItemToCallback($node, $this->printNodeCallback);
    }

    /**
     * This method gets the settings for all items which have custom settings.
     *
     * @return array;
     */
    protected function getItemsSettings(): array
    {
        if (empty($this->itemsSettings)) {
            $db = Factory::getDbo();

            $query = $db->getQuery(true)
                ->select([
                    '*',
                    sprintf(
                        'IF (IFNULL(settings_hash, %1$s) = %1$s, uid, CONCAT(uid, %2$s, settings_hash)) AS %3$s',
                        $db->quote(''),
                        $db->quote(':'),
                        $db->quoteName('key')
                    )
                ])
                ->from('#__osmap_items_settings')
                ->where('sitemap_id = ' . $db->quote($this->sitemap->id))
                ->where($db->quoteName('format') . ' = 2');

            $this->itemsSettings = $db->setQuery($query)->loadAssocList('key');
        }

        return $this->itemsSettings;
    }

    /**
     * Gets the item custom settings if set. If not set, returns false.
     *
     * @param string $key
     *
     * @return ?array[]
     */
    public function getItemCustomSettings(string $key): ?array
    {
        if (isset($this->itemsSettings[$key])) {
            return $this->itemsSettings[$key];
        }

        return null;
    }

    /**
     * This method gets the legacy settings for all items to be loaded avoiding
     * lost the custom settings for items after the migration to v4.2.1.
     *
     * @return array
     */
    protected function getLegacyItemsSettings(): array
    {
        if ($this->legacyItemsSettings === null) {
            $db = Factory::getDbo();

            $query = $db->getQuery(true)
                ->select('*')
                ->from('#__osmap_items_settings')
                ->where('sitemap_id = ' . $db->quote($this->sitemap->id))
                ->where($db->quoteName('format') . ' IS NULL');

            $this->legacyItemsSettings = $db->setQuery($query)->loadAssocList('uid');
        }

        return $this->legacyItemsSettings;
    }

    /**
     * Returns the settings based on the UID only. Used when we have legacy
     * settings on the database.
     *
     * @param string $uid
     *
     * @return ?array[]
     */
    protected function getLegacyItemCustomSettings(string $uid): ?array
    {
        if (isset($this->legacyItemsSettings[$uid])) {
            return $this->legacyItemsSettings[$uid];
        }

        return null;
    }

    /**
     * Sets the item's custom settings if exists. If no custom settings are
     * found and is a menu item, use the menu's settings. If is s subitem
     * (from plugins), we consider it already set the respective settings. But
     * if there is a custom setting for the item, we use that overriding what
     * was set in the plugin.
     *
     * @param Item $item
     *
     * @return void
     */
    public function setItemCustomSettings(Item $item)
    {
        // Check if the menu item has custom settings. If not, use the values from the menu
        // Check if there is a custom settings specific for this URL. Sometimes the same page has different URLs.
        // We can have different settings for items with the same UID, but different URLs
        $key      = $item->uid . ':' . $item->settingsHash;
        $settings = $this->getItemCustomSettings($key);

        // Check if there is a custom settings for all links with that UID (happens right after a migration from
        // versions before 4.0.0 or before 4.2.1)
        if (empty($settings)) {
            $settings = $this->getLegacyItemCustomSettings($item->uid);

            // The Joomla plugin changed the UID
            // from joomla.archive => joomla.archive.[id] and joomla.featured => joomla.featured[id]
            // So we need to try getting the settings from the old UID
            if ($settings === null) {
                if (preg_match('/^joomla.(archive|featured)/', $item->uid, $matches)) {
                    $settings = $this->getLegacyItemCustomSettings('joomla.' . $matches[1]);
                }
            }
        }

        if (empty($settings)) {
            // No custom settings, so let's use the menu's settings
            if ($item->isMenuItem) {
                $item->changefreq = $this->tmpItemDefaultSettings['changefreq'];
                $item->priority   = $this->tmpItemDefaultSettings['priority'];
            }

        } else {
            // Apply the custom settings
            $item->changefreq = $settings['changefreq'] ?? 'weekly';
            $item->priority   = (float)$settings['priority'] ?? .5;
            $item->published  = (bool)$settings['published'] ?? true;
        }
    }

    /**
     * Check if the parent is unpublished or ignored and makes sure to ignore any item on it's sublevel
     *
     * @param Item $item
     *
     * @return void
     */
    protected function checkParentIsUnpublished(Item $item)
    {
        // Check if this item belongs to a sub-level which needs to be unpublished
        if ($this->unpublishLevel !== false && $item->level > $this->unpublishLevel) {
            $item->set('published', false);
            $item->addAdminNote('COM_OSMAP_ADMIN_NOTE_PARENT_UNPUBLISHED');
        }

        // If the item is unpublished and the 'ignore' level is false, mark the level to ignore sub-items
        $displayable = $item->published
            && !$item->ignore
            && (!$item->duplicate || !$this->params->get('ignore_duplicated_uids', 1));
        if (!$displayable && $this->unpublishLevel === false) {
            $this->unpublishLevel = $item->level;
        }

        // If the item won't be ignored, make sure to reset the 'ignore' level
        if (
            $item->published
            && !$item->ignore
            && (!$item->duplicate || !$this->params->get('ignore_duplicated_uids', 1))
        ) {
            $this->unpublishLevel = false;
        }
    }

    /**
     * Returns the current menu item id
     *
     * @return int
     */
    public function getCurrentMenuItemId(): int
    {
        return (int)$this->currentMenuItemId;
    }

    /**
     * Removes circular reference
     */
    public function cleanup()
    {
        $this->sitemap           = null;
        $this->printNodeCallback = null;
        $this->params            = null;
        $this->currentMenu       = null;
    }
}
