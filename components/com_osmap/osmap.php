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
// no direct access
defined('_JEXEC') or die('Restricted access');

// Include dependencies
jimport('joomla.application.component.controller');

# For compatibility with older versions of Joola 2.5
if (!class_exists('JControllerLegacy')){
    class JControllerLegacy extends JController {

    }
}

require_once(JPATH_COMPONENT.'/displayer.php');

$controller = JControllerLegacy::getInstance('Xmap');
$controller->execute(JRequest::getVar('task'));
$controller->redirect();
