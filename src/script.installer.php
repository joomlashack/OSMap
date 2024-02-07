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

use Alledia\Installer\OSMap\Free\AbstractScript;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die();
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

$installPath = __DIR__ . (is_dir(__DIR__ . '/admin') ? '/admin' : '');
include $installPath . '/library/Installer/include.php';

// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class com_osmapInstallerScript extends AbstractScript
{
}
