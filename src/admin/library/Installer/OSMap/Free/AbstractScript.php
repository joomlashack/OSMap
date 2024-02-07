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

namespace Alledia\Installer\OSMap\Free;

use Alledia\Installer\OSMap\XmapConverter;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die();

// phpcs:enable PSR1.Files.SideEffects

class AbstractScript extends \Alledia\Installer\AbstractScript
{
    /**
     * @var bool
     */
    protected $isXmapDataFound = false;

    /**
     * @inheritDoc
     */
    protected function customPostFlight(string $type, InstallerAdapter $parent): void
    {
        if ($type == 'uninstall') {
            return;
        }

        // Check if XMap is installed, to start a migration
        $xmapConverter = new XmapConverter();

        // This attribute will be used by the custom template to display the option to migrate legacy sitemaps
        $this->isXmapDataFound = $this->findTable('#__xmap_sitemap') && $xmapConverter->checkXmapDataExists();

        // If Xmap plugins are still available, and we don't have the OSMap plugins yet,
        // save Xmap plugins params to re-apply after install OSMap plugins
        $xmapConverter->saveXmapPluginParamsIfExists();

        // Load Alledia Framework
        require_once JPATH_ADMINISTRATOR . '/components/com_osmap/include.php';

        switch ($type) {
            case 'install':
            case 'discover_install':
                $this->createDefaultSitemap();

                $link = HTMLHelper::_(
                    'link',
                    'index.php?option=com_plugins&view=plugins&filter.search=OSMap',
                    Text::_('COM_OSMAP_INSTALLER_PLUGINS_PAGE')
                );
                $this->sendMessage(Text::sprintf('COM_OSMAP_INSTALLER_GOTOPLUGINS', $link), 'warning');
                break;

            case 'update':
                $this->checkDatabase();
                $this->fixXMLMenus();
                $this->clearLanguageFiles();
                break;
        }

        $xmapConverter->moveXmapPluginsParamsToOSMapPlugins();
    }

    /**
     * Creates a default sitemap if no one is found.
     *
     * @return void
     */
    protected function createDefaultSitemap(): void
    {
        $db = Factory::getDbo();

        // Check if we have any sitemaps, otherwise let's create a default one
        $query      = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from('#__osmap_sitemaps');
        $noSitemaps = ((int)$db->setQuery($query)->loadResult()) === 0;

        if ($noSitemaps) {
            // Get all menus

            // Search for home menu and language if exists
            $subQuery = $db->getQuery(true)
                ->select('b.menutype, b.home, b.language, l.image, l.sef, l.title_native')
                ->from('#__menu AS b')
                ->leftJoin('#__languages AS l ON l.lang_code = b.language')
                ->where('b.home != 0')
                ->where('(b.client_id = 0 OR b.client_id IS NULL)');

            // Get all menu types with optional home menu and language
            $query = $db->getQuery(true)
                ->select('a.id, a.asset_id, a.menutype, a.title, a.description, a.client_id')
                ->select('c.home, c.language, c.image, c.sef, c.title_native')
                ->from('#__menu_types AS a')
                ->leftJoin('(' . $subQuery . ') c ON c.menutype = a.menutype')
                ->order('a.id');

            $db->setQuery($query);

            $menus = $db->loadObjectList();

            if (!empty($menus)) {
                $data = [
                    'name'       => 'Default Sitemap',
                    'is_default' => 1,
                    'published'  => 1,
                ];

                // Create the sitemap
                Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_osmap/tables');
                $row = Table::getInstance('Sitemap', 'OSMapTable');
                $row->save($data);

                $i = 0;
                foreach ($menus as $menu) {
                    $menuTypeId = $this->getMenuTypeId($menu->menutype);

                    $query = $db->getQuery(true)
                        ->set('sitemap_id = ' . $db->quote($row->id))
                        ->set('menutype_id = ' . $db->quote($menuTypeId))
                        ->set('priority = ' . $db->quote('0.5'))
                        ->set('changefreq = ' . $db->quote('weekly'))
                        ->set('ordering = ' . $db->quote($i++))
                        ->insert('#__osmap_sitemap_menus');
                    $db->setQuery($query)->execute();
                }
            }
        }
    }

    /**
     * Check if there are sitemaps in the old table. After migrate, remove
     * the table.
     *
     * On updates, verify current schema
     *
     * @return void
     */
    protected function checkDatabase(): void
    {
        $this->sendDebugMessage(__METHOD__);

        if ($this->findTable('#__osmap_sitemap')) {
            $this->migrateLegacyDatabase();

        } else {
            $this->checkDatabaseSchema();
        }
    }

    /**
     * @return void
     */
    protected function migrateLegacyDatabase(): void
    {
        $this->sendDebugMessage('Migrating legacy database');

        try {
            $db = $this->dbo;

            $db->transactionStart();

            // For the migration, as we only have new tables, make sure to have a clean start
            $db->setQuery('DELETE FROM ' . $db->quoteName('#__osmap_items_settings'))->execute();
            $db->setQuery('DELETE FROM ' . $db->quoteName('#__osmap_sitemap_menus'))->execute();
            $db->setQuery('DELETE FROM ' . $db->quoteName('#__osmap_sitemaps'))->execute();

            // Get legacy sitemaps
            $query    = $db->getQuery(true)
                ->select([
                    'id',
                    'title',
                    'is_default',
                    'state',
                    'created',
                    'selections',
                    'excluded_items',
                ])
                ->from('#__osmap_sitemap');
            $sitemaps = $db->setQuery($query)->loadObjectList();

            // Move the legacy sitemaps to the new table
            if ($sitemaps) {
                foreach ($sitemaps as $sitemap) {
                    if ($sitemap->created === $db->getNullDate()) {
                        $sitemap->created = Factory::getDate()->toSql();
                    }

                    $insertObject = (object)[
                        'id'         => $sitemap->id,
                        'name'       => $sitemap->title,
                        'is_default' => $sitemap->is_default,
                        'published'  => $sitemap->state,
                        'created_on' => $sitemap->created,
                    ];
                    $db->insertObject('#__osmap_sitemaps', $insertObject);

                    // Add the selected menus
                    $menus = json_decode($sitemap->selections, true);
                    if (is_array($menus)) {
                        foreach ($menus as $menuType => $menu) {
                            $menuTypeId = $this->getMenuTypeId($menuType);

                            if ($menuTypeId) {
                                // Menu type still exists
                                $insertObject = (object)[
                                    'sitemap_id'  => $sitemap->id,
                                    'menutype_id' => $menuTypeId,
                                    'priority'    => $menu['priority'],
                                    'changefreq'  => $menu['changefreq'],
                                    'ordering'    => $menu['ordering'],
                                ];
                                $db->insertObject('#__osmap_sitemap_menus', $insertObject);
                            }
                        }
                    }

                    $excludedItems = json_decode($sitemap->excluded_items ?? '', true);
                    if (is_array($excludedItems)) {
                        // Convert settings for excluded items
                        foreach ($excludedItems as $item) {
                            $uid = $this->convertItemUID($item[0]);

                            // Check if the item was already registered
                            $query = $db->getQuery(true)
                                ->select('COUNT(*)')
                                ->from('#__osmap_items_settings')
                                ->where([
                                    'sitemap_id = ' . $db->quote($sitemap->id),
                                    'uid = ' . $db->quote($uid),
                                ]);
                            $count = $db->setQuery($query)->loadResult();

                            if ($count == 0) {
                                $insertObject = (object)[
                                    'sitemap_id' => $sitemap->id,
                                    'uid'        => $uid,
                                    'published'  => 0,
                                    'changefreq' => 'weekly',
                                    'priority'   => '0.5',
                                ];
                                $db->insertObject('#__osmap_items_settings', $insertObject);

                            } else {
                                // Update the setting
                                $query = $db->getQuery(true)
                                    ->update('#__osmap_items_settings')
                                    ->set('published = 0')
                                    ->where([
                                        'sitemap_id = ' . $db->quote($sitemap->id),
                                        'uid = ' . $db->quote($uid),
                                    ]);
                                $db->setQuery($query)->execute();
                            }
                        }
                    }

                    // Convert custom settings for items
                    if ($this->findTable('#__osmap_items')) {
                        $query         = $db->getQuery(true)
                            ->select([
                                'uid',
                                'properties',
                            ])
                            ->from('#__osmap_items')
                            ->where('sitemap_id = ' . $db->quote($sitemap->id))
                            ->where('view = ' . $db->quote('xml'));
                        $modifiedItems = $db->setQuery($query)->loadObjectList();

                        if ($modifiedItems) {
                            foreach ($modifiedItems as $item) {
                                $item->properties = str_replace(';', '&', $item->properties);
                                parse_str($item->properties, $properties);

                                $item->uid = $this->convertItemUID($item->uid);

                                // Check if the item already exists to update, or insert
                                $query  = $db->getQuery(true)
                                    ->select('COUNT(*)')
                                    ->from('#__osmap_items_settings')
                                    ->where([
                                        'sitemap_id = ' . $db->quote($sitemap->id),
                                        'uid = ' . $db->quote($item->uid),
                                    ]);
                                $exists = (bool)$db->setQuery($query)->loadResult();

                                if ($exists) {
                                    $fields = [];

                                    // Check if the changefreq is set and set to update
                                    if (isset($properties['changefreq'])) {
                                        $fields = 'changefreq = ' . $db->quote($properties['changefreq']);
                                    }

                                    // Check if the priority is set and set to update
                                    if (isset($properties['priority'])) {
                                        $fields = 'priority = ' . $db->quote($properties['priority']);
                                    }

                                    // Update the item
                                    $query = $db->getQuery(true)
                                        ->update('#__osmap_items_settings')
                                        ->set($fields)
                                        ->where([
                                            'sitemap_id = ' . $db->quote($sitemap->id),
                                            'uid = ' . $db->quote($item->uid),
                                        ]);
                                    $db->setQuery($query)->execute();

                                } else {
                                    $insertObject = (object)[
                                        'sitemap_id' => $sitemap->id,
                                        'uid'        => $item->uid,
                                        'published'  => 1,
                                        'changefreq' => $properties['changefreq'] ?? 'weekly',
                                        'priority'   => $properties['priority'] ?? '0.5',
                                    ];

                                    $db->insertObject('#__osmap_items_settings', $insertObject);
                                }
                            }
                        }
                    }
                }
            }

            // Remove the old table
            $query = 'DROP TABLE IF EXISTS ' . $db->quoteName('#__osmap_items');
            $db->setQuery($query)->execute();

            // Remove the old table
            $query = 'DROP TABLE IF EXISTS ' . $db->quoteName('#__osmap_sitemap');
            $db->setQuery($query)->execute();

            $db->transactionCommit();

        } catch (\Throwable $error) {
            $this->sendErrorMessage($error);
            $db->transactionRollback();
        }
    }

    /**
     * Returns the id of the menutype.
     *
     * @param string $menuType
     *
     * @return int
     */
    protected function getMenuTypeId(string $menuType): int
    {
        $db = $this->dbo;

        $query = $db->getQuery(true)
            ->select('id')
            ->from('#__menu_types')
            ->where('menutype = ' . $db->quote($menuType));

        return (int)$db->setQuery($query)->loadResult();
    }

    /**
     * Converts a legacy UID to the new pattern. Instead of "com_contenta25",
     * "joomla.article.25". Returns the new UID
     *
     * @param string $uid
     *
     * @return string
     */
    protected function convertItemUID(string $uid): string
    {
        // Joomla articles in categories
        if (preg_match('#com_contentc[0-9]+a([0-9]+)#', $uid, $matches)) {
            return 'joomla.article.' . $matches[1];
        }

        // Joomla categories
        if (preg_match('#com_contentc([0-9]+)#', $uid, $matches)) {
            return 'joomla.category.' . $matches[1];
        }

        // Joomla articles
        if (preg_match('#com_contenta([0-9]+)#', $uid, $matches)) {
            return 'joomla.article.' . $matches[1];
        }

        // Joomla featured
        if (preg_match('#com_contentfeatureda([0-9]+)#', $uid, $matches)) {
            return 'joomla.featured.' . $matches[1];
        }

        // Menu items
        if (preg_match('#itemid([0-9]*)#', $uid, $matches)) {
            return 'menuitem.' . $matches[1];
        }

        return $uid;
    }

    /**
     * @return void
     */
    public function checkDatabaseSchema(): void
    {
        if (version_compare($this->schemaVersion, '5', 'ge')) {
            $this->sendDebugMessage('No DB Schema updates');
            return;
        }

        $db = $this->dbo;

        $this->sendDebugMessage('Checking database schema updates: v' . $this->schemaVersion);

        if ($this->findColumn('#__osmap_items_settings.format')) {
            $this->sendDebugMessage('UPDATE: items_settings.format');

            $db->setQuery(join(' ', [
                'ALTER TABLE `#__osmap_items_settings`',
                "MODIFY `format` TINYINT(1) UNSIGNED NULL DEFAULT '2'",
                "COMMENT 'Format of the setting: 1) Legacy Mode - UID Only; 2) Based on menu ID and UID'",
            ]))
                ->execute();
        }

        if ($this->findColumn('#__osmap_items_settings.url_hash')) {
            $this->sendDebugMessage('UPDATE: items_settings.url_hash');

            $db->setQuery(
                sprintf(
                    'ALTER TABLE %s CHANGE %s %s CHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci  NOT NULL DEFAULT %s',
                    $db->quoteName('#__osmap_items_settings'),
                    $db->quoteName('url_hash'),
                    $db->quoteName('settings_hash'),
                    $db->quote('')
                )
            )
                ->execute();
        }

        $this->dropConstraints([
            '#__osmap_sitemap_menus.fk_sitemaps',
            '#__osmap_sitemap_menus.fk_sitemaps_menus',
        ]);

        $this->addIndexes([
            '#__osmap_sitemaps.idx_default'          => ['is_default ASC', 'id ASC'],
            '#__osmap_items_settings.idx_sitemap_id' => ['sitemap_id ASC'],
            '#__osmap_sitemap_menus.idx_menutype_id' => ['menutype_id ASC'],
            '#__osmap_sitemap_menus.idx_sitemaps_id' => ['sitemap_id ASC'],
        ]);

        $this->dropIndexes([
            '#__osmap_sitemaps.default_idx',
            '#__osmap_sitemap_menus.idx_sitemap_menus',
            '#__osmap_sitemap_menus.fk_sitemaps_idx',
            '#__osmap_sitemap_menus.ordering_idx',
            '#__osmap_sitemap_menus.idx_ordering',
        ]);
    }

    /**
     * Adds new format=xml to existing xml menus
     *
     * @since v4.2.25
     */
    protected function fixXMLMenus()
    {
        $db      = $this->dbo;
        $siteApp = SiteApplication::getInstance('site');

        $query = $db->getQuery(true)
            ->select('id, link')
            ->from('#__menu')
            ->where([
                'client_id = ' . $siteApp->getClientId(),
                sprintf('link LIKE %s', $db->quote('%com_osmap%')),
                sprintf('link LIKE %s', $db->quote('%view=xml%')),
                sprintf('link NOT LIKE %s', $db->quote('%format=xml%')),
            ]);

        $menus = $db->setQuery($query)->loadObjectList();
        foreach ($menus as $menu) {
            $menu->link .= '&format=xml';
            $db->updateObject('#__menu', $menu, ['id']);
        }
    }

    /**
     * Clear localized language files from core folders
     *
     * @return void
     */
    protected function clearLanguageFiles()
    {
        $files = array_merge(
            Folder::files(JPATH_ADMINISTRATOR . '/language', '_osmap', true, true),
            Folder::files(JPATH_SITE . '/language', '_osmap', true, true)
        );

        foreach ($files as $file) {
            if (is_file($file)) {
                File::delete($file);
            }
        }
    }
}
