<?php
/**
 * @package   OSMap
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2007-2014 XMap - Joomla! Vargas - Guillermo Vargas. All rights reserved.
 * @copyright 2016-2021 Joomlashack.com. All rights reserved.
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

use Alledia\OSMap\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;

defined('_JEXEC') or die();

class OSMapTableSitemap extends Table
{
    /**
     * @var int Primary key
     */
    public $id = null;

    /**
     * @var string
     */
    public $name = null;

    /**
     * @var string
     */
    public $params = null;

    /**
     * @var string
     */
    public $created_on = null;

    /**
     * @var int
     */
    public $is_default = 0;

    /**
     * @var int
     */
    public $published = 1;

    /**
     * @var int
     */
    public $links_count = 0;

    /**
     * @var array
     */
    public $menus = [];

    /**
     * @var array
     */
    public $menus_priority = [];

    /**
     * @var array
     */
    public $menus_changefreq = [];

    /**
     * @var string
     */
    public $menus_ordering = '';

    /**
     * @param JDatabaseDriver $db
     */
    public function __construct($db)
    {
        parent::__construct('#__osmap_sitemaps', 'id', $db);
    }

    /**
     * @inheritDoc
     */
    public function bind($src, $ignore = '')
    {
        if (isset($src['params']) && is_array($src['params'])) {
            $registry = new Registry();
            $registry->loadArray($src['params']);
            $src['params'] = $registry->toString();
        }

        if (isset($src['metadata']) && is_array($src['metadata'])) {
            $registry = new Registry();
            $registry->loadArray($src['metadata']);
            $src['metadata'] = $registry->toString();
        }

        return parent::bind($src, $ignore);
    }

    /**
     * @inheritDoc
     */
    public function check()
    {
        if (empty($this->name)) {
            $this->setError(Text::_('COM_OSMAP_MSG_SITEMAP_MUST_HAVE_NAME'));

            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function store($updateNulls = false)
    {
        $db   = Factory::getDbo();
        $date = Factory::getDate();

        if (!$this->id) {
            $this->created_on = $date->toSql();
        }

        // Make sure we have only one default sitemap
        if ($this->is_default) {
            // Set as not default any other sitemap
            $query = $db->getQuery(true)
                ->update('#__osmap_sitemaps')
                ->set('is_default = 0');

            $db->setQuery($query)->execute();

        } else {
            // Check if we have another default sitemap. If not, force this as default
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from('#__osmap_sitemaps')
                ->where('is_default = 1')
                ->where('id <> ' . $db->quote($this->id));

            $count = (int)$db->setQuery($query)->loadResult();

            if ($count == 0) {
                $this->is_default = 1;

                Factory::getApplication()->enqueueMessage(
                    Text::_('COM_OSMAP_MSG_SITEMAP_FORCED_AS_DEFAULT'),
                    'info'
                );
            }
        }

        // Get the menus
        $menus           = $this->menus;
        $menusPriority   = $this->menus_priority;
        $menusChangeFreq = $this->menus_changefreq;
        $menusOrdering   = explode(',', $this->menus_ordering);

        unset($this->menus, $this->menus_priority, $this->menus_changefreq, $this->menus_ordering);

        // Store the sitemap data
        $result = parent::store($updateNulls);

        if ($result) {
            // Remove the current menus
            $this->removeMenus();

            if (!empty($menus)) {
                $ordering = 0;

                // Store the menus for this sitemap
                foreach ($menus as $menuId) {
                    // Get the index of the selected menu in the ordering array
                    $index = array_search('menu_' . $menuId, $menusOrdering);

                    $query = $db->getQuery(true)
                        ->insert('#__osmap_sitemap_menus')
                        ->set([
                            'sitemap_id = ' . $db->quote($this->id),
                            'menutype_id = ' . $db->quote($menuId),
                            'priority = ' . $db->quote($menusPriority[$index]),
                            'changefreq = ' . $db->quote($menusChangeFreq[$index]),
                            'ordering = ' . $ordering
                        ]);
                    $db->setQuery($query)->execute();

                    $ordering++;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Remove all the menus for the given sitemap
     *
     * @return void
     */
    public function removeMenus()
    {
        if ($this->id) {
            $db    = Factory::getDbo();
            $query = $db->getQuery(true)
                ->delete('#__osmap_sitemap_menus')
                ->where('sitemap_id = ' . $db->quote($this->id));

            $db->setQuery($query)->execute();
        }
    }

    /**
     * @inheritDoc
     */
    public function load($keys = null, $reset = true)
    {
        if (parent::load($keys, $reset)) {
            // Load the menus information
            $db       = Factory::getDbo();
            $ordering = [];

            $query = $db->getQuery(true)
                ->select('*')
                ->from('#__osmap_sitemap_menus')
                ->where('sitemap_id = ' . $db->quote($this->id))
                ->order('ordering');

            $menusRows = $db->setQuery($query)->loadObjectList();
            if ($menusRows) {
                foreach ($menusRows as $menu) {
                    $this->menus[]            = $menu->menutype_id;
                    $this->menus_priority[]   = $menu->priority;
                    $ordering[]               = 'menu_' . $menu->menutype_id;
                    $this->menus_changefreq[] = $menu->changefreq;
                }
            }

            $this->menus_ordering = join(',', $ordering);

            return true;
        }

        return false;
    }
}
