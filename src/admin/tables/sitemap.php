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

use Alledia\OSMap\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;

defined('_JEXEC') or die();

class OSMapTableSitemap extends Table
{
    /**
     * @var int
     */
    public $id = null;

    /**
     * @var string
     */
    public $name = null;

    /**
     * @var string|Registry
     */
    public $params = null;

    /**
     * @var int
     */
    public $is_default = null;

    /**
     * @var int
     */
    public $published = null;

    /**
     * @var string|DateTime
     */
    public $created_on = null;

    /**
     * @var int
     */
    public $links_count = null;

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
     * @throws Exception
     */
    public function store($updateNulls = false)
    {
        if (empty($this->get('id'))) {
            $this->set('created_on', Factory::getDate()->toSql());
        }

        $this->checkDefault();

        $menuKeys = [
            'menus',
            'menus_priority',
            'menus_changefreq',
            'menus_ordering'
        ];
        $menus    = [];
        foreach ($menuKeys as $menuKey) {
            $menus[$menuKey] = $this->get($menuKey);
            if (isset($this->{$menuKey})) {
                unset($this->{$menuKey});
            }
        }

        if (parent::store($updateNulls)) {
            return $this->updateMenus($menus);
        }

        return false;
    }

    /**
     * Make sure we have one and only one default sitemap
     *
     * @return void
     * @throws Exception
     */
    protected function checkDefault()
    {
        $db = Factory::getDbo();

        // Make sure we have only one default sitemap
        if ($this->get('is_default')) {
            // Set all other sitemaps as not default
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
                ->where('id <> ' . (int)$this->get('id'));

            $count = (int)$db->setQuery($query)->loadResult();

            if ($count == 0) {
                $this->set('is_default', 1);

                Factory::getApplication()->enqueueMessage(
                    Text::_('COM_OSMAP_MSG_SITEMAP_FORCED_AS_DEFAULT'),
                    'info'
                );
            }
        }
    }

    /**
     * Update the related menu table
     *
     * @param array $menus
     *
     * @return bool
     */
    protected function updateMenus(array $menus): bool
    {
        $id = (int)$this->id;

        if ($id) {
            $db           = Factory::getDbo();
            $ordering     = 1;
            $insertValues = [];

            $menuOrder  = array_map('trim', explode(',', $menus['menus_ordering'] ?? ''));
            $changeFreq = $menus['menus_changefreq'] ?? null;
            $priority   = $menus['menus_priority'] ?? null;
            $menus      = $menus['menus'] ?? null;

            // Store the menus for this sitemap
            foreach ($menus as $menuId) {
                // Get the index of the selected menu in the ordering array
                $index = array_search('menu_' . $menuId, $menuOrder);

                $insertValues[] = [
                    'sitemap_id'  => (int)$this->get('id'),
                    'menutype_id' => (int)$menuId,
                    'priority'    => (float)$priority[$index],
                    'changefreq'  => $db->quote($changeFreq[$index]),
                    'ordering'    => $ordering++
                ];
            }

            // Clear the menu list
            $query = $db->getQuery(true)
                ->delete('#__osmap_sitemap_menus')
                ->where('sitemap_id = ' . $id);

            $db->setQuery($query)->execute();

            // Insert the updated list
            foreach ($insertValues as $insertValue) {
                $query = $db->getQuery(true)
                    ->insert('#__osmap_sitemap_menus')
                    ->columns(array_keys($insertValue))
                    ->values(join(',', $insertValue));

                $db->setQuery($query)->execute();
            }
        }

        return true;
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
                ->where('sitemap_id = ' . (int)$this->get('id'))
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
