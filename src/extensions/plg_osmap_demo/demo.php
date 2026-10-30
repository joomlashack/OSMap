<?php

/**
 * @package   OSMap
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2007-2014 XMap - Joomla! Vargas - Guillermo Vargas. All rights reserved.
 * @copyright 2016-2026 Joomlashack.com. All rights reserved.
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

use Alledia\OSMap\Plugin\Base;
use Alledia\OSMap\Sitemap\Collector;
use Alledia\OSMap\Sitemap\Item;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die();
// phpcs:enable PSR1.Files.SideEffects
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

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
