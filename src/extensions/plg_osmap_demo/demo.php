<?php
/**
 * @package   OSMap-Pro
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2015-2021 Joomlashack.com. All rights reserved
 * @license   https://www.gnu.org/licenses/gpl.html GNU/GPL
 *
 * This file is part of OSMap-Pro.
 *
 * OSMap-Pro is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * OSMap-Pro is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OSMap-Pro.  If not, see <https://www.gnu.org/licenses/>.
 */

use Alledia\OSMap\Plugin\Base;
use Alledia\OSMap\Sitemap\Collector;
use Alledia\OSMap\Sitemap\Item;
use Joomla\Registry\Registry;

defined('_JEXEC') or die();

class PlgOsmapDemo extends Base
{
    public function getComponentElement()
    {
        return 'com_demo';
    }

    /**
     * Collect all the nodes for the current menu item and print via the collector object
     *
     * @param Collector $collector
     * @param Item      $parent
     * @param Registry  $params
     *
     * @return void
     * @throws Exception
     */
    public function getTree(Collector $collector, Item $parent, Registry $params)
    {
        $node = (object)[
            'id'         => $parent->id,
            'name'       => null, // The view title
            'uid'        => null, // A universal unique ID for this node
            'modified'   => null, // Modification date for this page
            'browserNav' => $parent->browserNav,
            'priority'   => $this->params['cat_priority'],
            'changefreq' => $this->params['cat_changefreq'],
            'link'       => null // Link to the page
        ];

        $collector->printNode($node);
    }
}
