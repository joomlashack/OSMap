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

namespace Alledia\OSMap\Sitemap\ItemAdapter;

use Alledia\OSMap\Sitemap\Item;

defined('_JEXEC') or die();

class Generic implements AdapterInterface
{
    /**
     * @var Item
     */
    protected $item;

    /**
     * @param Item $item
     *
     * @return void
     */
    public function __construct(Item $item)
    {
        $this->item = $item;
    }

    /**
     * @inheritDoc
     */
    public function checkVisibilityForRobots()
    {
        // Always visible for the Free version
        return true;
    }
}
