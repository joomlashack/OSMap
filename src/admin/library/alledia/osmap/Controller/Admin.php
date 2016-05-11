<?php
/**
 * @package   OSMap
 * @copyright 2007-2014 XMap - Joomla! Vargas - Guillermo Vargas. All rights reserved.
 * @copyright 2016 Open Source Training, LLC. All rights reserved.
 * @contact   www.alledia.com, support@alledia.com
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 */

namespace Alledia\OSMap\Controller;

defined('_JEXEC') or die();

jimport('joomla.application.component.controlleradmin');

abstract class Admin extends \JControllerAdmin
{
    protected function checkToken()
    {
        \JSession::checkToken() or jexit(\JText::_('JINVALID_TOKEN'));
    }
}
