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

defined('_JEXEC') or die();

use Alledia\Framework\AutoLoader;
use Alledia\OSMap\Helper\General;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;

try {
    $frameworkPath = JPATH_SITE . '/libraries/allediaframework/include.php';
    if (
        is_file($frameworkPath) == false
        || (include $frameworkPath) == false
    ) {
        $app = Factory::getApplication();

        if ($app->isClient('administrator')) {
            $app->enqueueMessage('[OSMap] Joomlashack framework not found', 'error');
        }

        return false;
    }

    if (defined('ALLEDIA_FRAMEWORK_LOADED') && defined('OSMAP_LOADED') == false) {
        define('OSMAP_LOADED', 1);
        define('OSMAP_ADMIN_PATH', JPATH_ADMINISTRATOR . '/components/com_osmap');
        define('OSMAP_SITE_PATH', JPATH_SITE . '/components/com_osmap');
        define('OSMAP_LIBRARY_PATH', OSMAP_ADMIN_PATH . '/library');

        define('OSMAP_LICENSE', is_file(OSMAP_LIBRARY_PATH . '/Alledia/OSMap/Services/Pro.php') ? 'pro' : 'free');

        AutoLoader::register('Alledia', OSMAP_LIBRARY_PATH . '/Alledia');
        AutoLoader::register('Pimple', OSMAP_LIBRARY_PATH . '/Pimple');

        PluginHelper::importPlugin('osmap');

        General::loadOptionLanguage();

        Table::addIncludePath(OSMAP_ADMIN_PATH . '/tables');
        Form::addFieldPath(OSMAP_ADMIN_PATH . '/fields');
        Form::addFormPath(OSMAP_ADMIN_PATH . '/form');
        HTMLHelper::addIncludePath(OSMAP_ADMIN_PATH . '/helpers/html');

        if (Factory::getApplication()->getName() == 'administrator') {
            HTMLHelper::_('alledia.fontawesome');
        }

        Log::addLogger(['text_file' => 'com_osmap.errors.php'], Log::ALL, ['com_osmap']);
    }

} catch (Throwable $error) {
    Factory::getApplication()->enqueueMessage('[OSMap] Unable to initialize: ' . $error->getMessage(), 'error');

    return false;
}

return defined('ALLEDIA_FRAMEWORK_LOADED') && defined('OSMAP_LOADED');
