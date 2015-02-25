<?php
/**
 * @package   OSMap
 * @copyright 2007-2014 XMap - Joomla! Vargas. All rights reserved.
 * @copyright 2015 Alledia.com, All rights reserved.
 * @author    Guillermo Vargas <guille@vargas.co.cr>
 * @author    Alledia <support@alledia.com>
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 *
 * This file is part of OSMap.
 *
 * OSMap is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * OSMap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OSMap. If not, see <http://www.gnu.org/licenses/>.
 */


// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Xmap component helper.
 *
 * @package     Xmap
 * @subpackage  com_xmap
 * @since       2.0
 */
class XmapHelper
{
    /**
     * Configure the Linkbar.
     *
     * @param    string  The name of the active view.
     */
    public static function addSubmenu($vName)
    {
        $version = new JVersion;

        if (version_compare($version->getShortVersion(), '3.0.0', '<')) {
            JSubMenuHelper::addEntry(
                JText::_('Xmap_Submenu_Sitemaps'),
                'index.php?option=com_xmap',
                $vName == 'sitemaps'
            );
            JSubMenuHelper::addEntry(
                JText::_('Xmap_Submenu_Extensions'),
                'index.php?option=com_plugins&view=plugins&filter_folder=xmap',
                $vName == 'extensions');
        } else {
            JHtmlSidebar::addEntry(
                JText::_('Xmap_Submenu_Sitemaps'),
                'index.php?option=com_xmap',
                $vName == 'sitemaps'
            );
            JHtmlSidebar::addEntry(
                JText::_('Xmap_Submenu_Extensions'),
                'index.php?option=com_plugins&view=plugins&filter_folder=xmap',
                $vName == 'extensions');
        }
    }
}
