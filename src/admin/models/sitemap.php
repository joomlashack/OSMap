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
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;

defined('_JEXEC') or die();


class OSMapModelSitemap extends AdminModel
{
    /**
     * @inheritDoc
     */
    public function getTable($name = 'Sitemap', $prefix = 'OSMapTable', $options = [])
    {
        return Table::getInstance($name, $prefix, $options);
    }

    /**
     * @inheritDoc
     */
    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_osmap.sitemap', 'sitemap', ['control' => 'jform', 'load_data' => $loadData]);
        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * @inheritDoc
     */
    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_osmap.edit.sitemap.data', []);

        if (empty($data)) {
            $data = $this->getItem();

            // Load some defaults for new sitemap
            $id = $data->get('id');
            if (empty($id)) {
                $data->set('published', 1);
                $data->set('created', Factory::getDate()->toSql());
            }

            // Load the menus
            if ($id) {
                $db    = Factory::getDbo();
                $query = $db->getQuery(true)
                    ->select('*')
                    ->from('#__osmap_sitemap_menus')
                    ->where('sitemap_id = ' . $db->quote($id))
                    ->order('ordering');
                $menus = $db->setQuery($query)->loadObjectList();

                $data->menus = [];

                foreach ($menus as $menu) {
                    $data->menus[$menu->menutype_id] = [
                        'priority'   => $menu->priority,
                        'changefreq' => $menu->changefreq
                    ];
                }
            }
        }

        return $data;
    }
}
