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

namespace Alledia\Installer\OSMap;

use Alledia\Framework\Joomla\Extension\Generic;
use Joomla\CMS\Factory;

defined('_JEXEC') or die();

class XmapConverter
{
    /**
     * @var array
     */
    protected $xmapPluginsParams = [];

    /**
     * List of refactored Xmap plugins to migrate the settings
     *
     * @var array
     */
    protected $refactoredXmapPlugins = ['com_content' => 'joomla'];

    /**
     * Look for the Xmap data to suggest a data migration
     *
     * @return bool True if Xmap data was found
     */
    public function checkXmapDataExists(): bool
    {
        $db = Factory::getDbo();

        // Do we have any Xmap sitemap?
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from('#__xmap_sitemap');

        $total = (int)$db->setQuery($query)->loadResult();

        return $total > 0;
    }

    /**
     * Save the Xmap plugins params into the new plugins. Receives a list of
     * plugin names to look for params.
     *
     * @return void
     */
    public function saveXmapPluginParamsIfExists()
    {
        $db = Factory::getDbo();

        $query         = $db->getQuery(true)
            ->select([
                'element',
                'params'
            ])
            ->from('#__extensions')
            ->where([
                'type = "plugin"',
                'folder = "xmap"',
                'element IN ("' . implode('","', array_keys($this->refactoredXmapPlugins)) . '")'
            ]);
        $legacyPlugins = $db->setQuery($query)->loadObjectList();

        // Check if the respective OSMap plugin is already installed. If so, do not save its params to not override.
        if ($legacyPlugins) {
            foreach ($legacyPlugins as $plugin) {
                $query         = $db->getQuery(true)
                    ->select('extension_id')
                    ->from('#__extensions')
                    ->where([
                        'type = "plugin"',
                        'folder = "osmap"',
                        'element = "' . $plugin->element . '"'
                    ]);
                $osmapPluginID = $db->setQuery($query)->loadResult();

                if (empty($osmapPluginID)) {
                    $this->xmapPluginsParams[] = $plugin;
                }
            }
        }
    }

    /**
     * This method move the Xmap plugins' params to the OSMap plugins.
     *
     * @return void
     */
    public function moveXmapPluginsParamsToOSMapPlugins()
    {
        $db = Factory::getDbo();

        if (!empty($this->xmapPluginsParams)) {
            foreach ($this->xmapPluginsParams as $plugin) {
                // Look for the OSMap plugin
                $query         = $db->getQuery(true)
                    ->select('extension_id')
                    ->from('#__extensions')
                    ->where([
                        'type = "plugin"',
                        'folder = "osmap"',
                        'element = ' . $db->quote($this->refactoredXmapPlugins[$plugin->element] ?? '')
                    ]);
                $osmapPluginID = $db->setQuery($query)->loadResult();

                if (!empty($osmapPluginID)) {
                    $query = $db->getQuery(true)
                        ->update('#__extensions')
                        ->set('params = ' . $db->quote(addslashes($plugin->params)))
                        ->where('extension_id = ' . $osmapPluginID);
                    $db->setQuery($query)->execute();
                }
            }
        }
    }

    /**
     * Migrates data from Xmap to OSMap.
     *
     * @return void
     */
    public function migrateData()
    {
        $result = (object)[
            'success' => false
        ];

        $db = Factory::getDbo();
        $db->transactionStart();

        try {
            // Do we have any Xmap sitemap?
            $sitemapFailedIds = [];
            $itemFailedIds    = [];
            $query            = $db->getQuery(true)
                ->select('*')
                ->from('#__xmap_sitemap');
            $sitemaps         = $db->setQuery($query)->loadObjectList();

            if ($sitemaps) {
                // Cleanup the db tables
                $tables = [
                    '#__osmap_items_settings',
                    '#__osmap_sitemap_menus',
                    '#__osmap_sitemaps'
                ];
                foreach ($tables as $table) {
                    $db->setQuery(
                        $db->getQuery(true)->delete($db->quoteName($table))
                    )->execute();
                }

                // Import the sitemaps
                foreach ($sitemaps as $sitemap) {
                    $query = $db->getQuery(true)
                        ->set([
                            $db->quoteName('id') . '=' . $db->quote($sitemap->id),
                            $db->quoteName('name') . '=' . $db->quote($sitemap->title),
                            $db->quoteName('is_default') . '=' . $db->quote($sitemap->is_default),
                            $db->quoteName('published') . '=' . $db->quote($sitemap->state),
                            $db->quoteName('created_on') . '=' . $db->quote($sitemap->created)
                        ])
                        ->insert('#__osmap_sitemaps');

                    if ($db->setQuery($query)->execute()) {
                        // Add the selected menus to the correct table
                        if ($menus = json_decode($sitemap->selections, true)) {
                            foreach ($menus as $menuType => $menu) {
                                if ($menuTypeId = $this->getMenuTypeId($menuType)) {
                                    // Convert the selection of menus into a row
                                    $query = $db->getQuery(true)
                                        ->insert('#__osmap_sitemap_menus')
                                        ->columns([
                                            'sitemap_id',
                                            'menutype_id',
                                            'priority',
                                            'changefreq',
                                            'ordering'
                                        ])
                                        ->values(
                                            implode(
                                                ',',
                                                [
                                                    $db->quote($sitemap->id),
                                                    $db->quote($menuTypeId),
                                                    $db->quote($menu['priority']),
                                                    $db->quote($menu['changefreq']),
                                                    $db->quote($menu['ordering'])
                                                ]
                                            )
                                        );
                                    $db->setQuery($query)->execute();
                                }
                            }
                        }

                        // Convert settings about excluded items
                        if ($sitemap->excluded_items ?? null) {
                            if ($excludedItems = json_decode($sitemap->excluded_items, true)) {
                                foreach ($excludedItems as $item) {
                                    $uid = $this->convertItemUID($item[0]);

                                    // Check if the item was already registered
                                    $query = $db->getQuery(true)
                                        ->select('COUNT(*)')
                                        ->from('#__osmap_items_settings')
                                        ->where([
                                            'sitemap_id = ' . $db->quote($sitemap->id),
                                            'uid = ' . $db->quote($uid)
                                        ]);
                                    $count = $db->setQuery($query)->loadResult();

                                    if ($count == 0) {
                                        // Insert the settings
                                        $query = $db->getQuery(true)
                                            ->insert('#__osmap_items_settings')
                                            ->columns([
                                                'sitemap_id',
                                                'uid',
                                                'published',
                                                'changefreq',
                                                'priority'
                                            ])
                                            ->values(
                                                implode(
                                                    ',',
                                                    [
                                                        $sitemap->id,
                                                        $db->quote($uid),
                                                        0,
                                                        $db->quote('weekly'),
                                                        $db->quote('0.5')
                                                    ]
                                                )
                                            );
                                    } else {
                                        // Update the setting
                                        $query = $db->getQuery(true)
                                            ->update('#__osmap_items_settings')
                                            ->set('published = 0')
                                            ->where([
                                                'sitemap_id = ' . $db->quote($sitemap->id),
                                                'uid = ' . $db->quote($uid)
                                            ]);
                                    }
                                    $db->setQuery($query)->execute();
                                }
                            }
                        }

                        // Convert custom settings for items
                        $query = $db->getQuery(true)
                            ->select([
                                'uid',
                                'properties'
                            ])
                            ->from('#__xmap_items')
                            ->where('sitemap_id = ' . $db->quote($sitemap->id))
                            ->where('view = ' . $db->quote('xml'));

                        if ($modifiedItems = $db->setQuery($query)->loadObjectList()) {
                            foreach ($modifiedItems as $item) {
                                $item->properties = str_replace(';', '&', $item->properties);
                                parse_str($item->properties, $properties);

                                $item->uid = $this->convertItemUID($item->uid);

                                // Check if the item already exists to update, or insert
                                $query = $db->getQuery(true)
                                    ->select('COUNT(*)')
                                    ->from('#__osmap_items_settings')
                                    ->where([
                                        'sitemap_id = ' . $db->quote($sitemap->id),
                                        'uid = ' . $db->quote($item->uid)
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
                                            'uid = ' . $db->quote($item->uid)
                                        ]);

                                } else {
                                    $columns = [
                                        'sitemap_id',
                                        'uid',
                                        'published'
                                    ];

                                    $values = [
                                        $db->quote($sitemap->id),
                                        $db->quote($item->uid),
                                        1
                                    ];

                                    // Check if the changefreq is set and set to update
                                    if (isset($properties['changefreq'])) {
                                        $columns[] = 'changefreq';
                                        $values[]  = 'changefreq = ' . $db->quote($properties['changefreq']);
                                    }

                                    // Check if the priority is set and set to update
                                    if (isset($properties['priority'])) {
                                        $columns[] = 'priority';
                                        $values[]  = 'priority = ' . $db->quote($properties['priority']);
                                    }

                                    // Insert a new item
                                    $query = $db->getQuery(true)
                                        ->insert('#__osmap_items_settings')
                                        ->columns($columns)
                                        ->values(implode(',', $values));
                                }
                                $db->setQuery($query)->execute();
                            }
                        }
                    } else {
                        $sitemapFailedIds = $sitemap->id;
                    }
                }
            }

            if ($sitemapFailedIds || $itemFailedIds) {
                throw new \Exception('Failed the sitemap or item migration');
            }

            /*
             * Menu Migration
             */
            $xmap  = new Generic('Xmap', 'component');
            $osmap = new Generic('OSMap', 'component');

            // Remove OSMap menus
            $query = $db->getQuery(true)
                ->delete('#__menu')
                ->where('type = ' . $db->quote('component'))
                ->where('component_id = ' . $db->quote($osmap->getId()));
            $db->setQuery($query);
            $db->execute();

            // Get the Xmap menus
            $query = $db->getQuery(true)
                ->select('*')
                ->from('#__menu')
                ->where('type = ' . $db->quote('component'))
                ->where('component_id = ' . $db->quote($xmap->getId()));
            $db->setQuery($query);
            $xmapMenus = $db->loadObjectList();

            if (!empty($xmapMenus)) {
                // Convert each menu to OSMap
                foreach ($xmapMenus as $menu) {
                    $query = $db->getQuery(true)
                        ->set('title = ' . $db->quote($this->replaceXmapByOSMap($menu->title)))
                        ->set('alias = ' . $db->quote($this->replaceXmapByOSMap($menu->alias)))
                        ->set('path = ' . $db->quote($this->replaceXmapByOSMap($menu->path)))
                        ->set('link = ' . $db->quote($this->replaceXmapByOSMap($menu->link)))
                        ->set('img = ' . $db->quote($this->replaceXmapByOSMap($menu->img)))
                        ->set('component_id = ' . $db->quote($osmap->getId()))
                        ->update('#__menu')
                        ->where('id = ' . $db->quote($menu->id));
                    $db->setQuery($query);
                    $db->execute();
                }
            }

            // Disable Xmap
            $query = $db->getQuery(true)
                ->set('enabled = 0')
                ->update('#__extensions')
                ->where('extension_id = ' . $db->quote($xmap->getId()));
            $db->setQuery($query);
            $db->execute();

            // Clean up Xmap db tables
            $db->setQuery('DELETE FROM ' . $db->quoteName('#__xmap_sitemap'));
            $db->execute();

            $db->setQuery('DELETE FROM ' . $db->quoteName('#__xmap_items'));
            $db->execute();

            $db->transactionCommit();

            $result->success = true;
        } catch (\Exception $e) {
            $db->transactionRollback();
            var_dump($e);
        }

        echo json_encode($result);
    }

    /**
     * Replaces the Xmap strings in multiple formats, changing to OSMap.
     *
     * @param string $str
     *
     * @return string
     */
    protected function replaceXmapByOSMap(string $str): string
    {
        $replacements = [
            'XMAP' => 'OSMAP',
            'XMap' => 'OSMap',
            'xMap' => 'OSMap',
            'xmap' => 'osmap',
        ];

        return str_replace(
            array_keys($replacements),
            $replacements,
            $str
        );
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
        $db = Factory::getDbo();

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
}
