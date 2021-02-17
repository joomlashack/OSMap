<?php
/**
 * @package   OSMap
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2007-2014 XMap - Joomla! Vargas - Guillermo Vargas. All rights reserved.
 * @copyright 2016-2021 Joomlashack.com. All rights reserved.
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
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
 * along with OSMap.  If not, see <http://www.gnu.org/licenses/>.
 */

defined('_JEXEC') or die();

use Alledia\Framework;
use Alledia\OSMap;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;

// Alledia Framework
if (!defined('ALLEDIA_FRAMEWORK_LOADED')) {
    $allediaFrameworkPath = JPATH_SITE . '/libraries/allediaframework/include.php';

    if (file_exists($allediaFrameworkPath)) {
        require_once $allediaFrameworkPath;
    } else {
        JFactory::getApplication()
            ->enqueueMessage('[OSMap] Alledia framework not found', 'error');
    }
}

if (!defined('OSMAP_LOADED')) {
    define('OSMAP_LOADED', 1);
    define('OSMAP_ADMIN_PATH', JPATH_ADMINISTRATOR . '/components/com_osmap');
    define('OSMAP_SITE_PATH', JPATH_SITE . '/components/com_osmap');
    define('OSMAP_LIBRARY_PATH', OSMAP_ADMIN_PATH . '/library');

    // Define the constant for the license
    define(
        'OSMAP_LICENSE',
        file_exists(OSMAP_LIBRARY_PATH . '/alledia/osmap/Services/Pro.php') ? 'pro' : 'free'
    );

    // Setup autoload libraries
    Framework\AutoLoader::register('Alledia\OSMap', OSMAP_LIBRARY_PATH . '/alledia/osmap');
    Framework\AutoLoader::register('Pimple', OSMAP_LIBRARY_PATH . '/pimple/pimple');

    PluginHelper::importPlugin('osmap');

    // Load the language files
    OSMap\Helper\General::loadOptionLanguage('com_osmap', OSMAP_ADMIN_PATH, OSMAP_SITE_PATH);

    Table::addIncludePath(OSMAP_ADMIN_PATH . '/tables');
    Form::addFieldPath(OSMAP_ADMIN_PATH . '/fields');
    Form::addFormPath(OSMAP_ADMIN_PATH . '/form');
    HTMLHelper::addIncludePath(OSMAP_ADMIN_PATH . '/helpers/html');

    Log::addLogger(['text_file' => 'com_osmap.errors.php'], Log::ALL, ['com_osmap']);
}
