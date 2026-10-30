<?php

/**
 * @package   OSMap
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2026 Joomlashack.com. All rights reserved
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

namespace Tests\Support\Fixtures;

use Joomla\CMS\Uri\Uri;

/**
 * Joomla works out the site root from the request and global configuration. This
 * sets it directly, so code that calls Uri::root() runs outside a Joomla site.
 */
class SiteUri extends Uri
{
    /**
     * @param string $url The site root, such as https://www.example.com/joomla
     *
     * @return void
     */
    public static function setSite(string $url): void
    {
        $site = new Uri(rtrim($url, '/'));

        static::$root = [
            'prefix' => $site->toString(['scheme', 'host', 'port']),
            'path'   => $site->getPath(),
        ];
        static::$base      = static::$root;
        static::$current   = null;
        static::$instances = ['SERVER' => new Uri($site->toString() . '/index.php')];
    }

    /**
     * @return void
     */
    public static function reset(): void
    {
        static::$root      = [];
        static::$base      = [];
        static::$current   = null;
        static::$instances = [];
    }
}
