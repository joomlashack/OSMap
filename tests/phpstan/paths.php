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

use Tests\Support\Paths;

require_once __DIR__ . '/../Support/Paths.php';

/*
 * The external checkouts the source needs for class discovery, found the same
 * way as for the unit tests. Joomla 3 has no com_content/src folder.
 */
$joomla = Paths::joomla();

return [
    'parameters' => [
        'scanDirectories' => array_values(array_filter(
            [
                Paths::framework() . '/src',
                Paths::installer() . '/src/library',
                $joomla . '/libraries/src',
                $joomla . '/libraries/vendor',
                $joomla . '/components/com_content/src',
            ],
            'is_dir'
        )),
    ],
];
