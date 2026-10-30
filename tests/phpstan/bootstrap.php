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

// phpcs:disable PSR1.Files.SideEffects

/*
 * Constants Joomla and the OSMap bootstrap define at runtime. The values
 * don't matter to PHPStan, it only needs to know the constants exist.
 */
$constants = [
    '_JEXEC',
    'JPATH_ADMINISTRATOR',
    'JPATH_COMPONENT',
    'JPATH_COMPONENT_ADMINISTRATOR',
    'JPATH_PLUGINS',
    'JPATH_SITE',
    'ALLEDIA_FRAMEWORK_LOADED',
    'OSMAP_ADMIN_PATH',
    'OSMAP_LIBRARY_PATH',
    'OSMAP_LICENSE',
    'OSMAP_LOADED',
    'OSMAP_PLUGIN_JOOMLA_LOADED',
    'OSMAP_SITE_PATH',
];

foreach ($constants as $constant) {
    if (defined($constant) == false) {
        define($constant, '');
    }
}
