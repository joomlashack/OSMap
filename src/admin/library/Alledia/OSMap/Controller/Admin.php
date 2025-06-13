<?php

/**
 * @package   OSMap
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2007-2014 XMap - Joomla! Vargas - Guillermo Vargas. All rights reserved.
 * @copyright 2016-2025 Joomlashack.com. All rights reserved.
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

namespace Alledia\OSMap\Controller;

use Alledia\OSMap\Factory;
use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Session\Session;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die();
// phpcs:enable PSR1.Files.SideEffects

abstract class Admin extends AdminController
{
    /**
     * @inheritDoc
     */
    public function execute($task)
    {
        $this->task = $task;

        $task = strtolower($task);

        PluginHelper::importPlugin('osmap');

        $controllerName = strtolower(str_replace('OSMapController', '', get_class($this)));
        $eventParams    = [$controllerName, $task];
        $results        = Factory::getApplication()->triggerEvent('osmapOnBeforeExecuteTask', $eventParams);

        // Check if any of the plugins returned the exit signal
        if (is_array($results) && in_array('exit', $results, true)) {
            Factory::getApplication()->enqueueMessage('COM_OSMAP_MSG_TASK_STOPPED_BY_PLUGIN', 'warning');

            return null;
        }

        if (isset($this->taskMap[$task])) {
            $doTask = $this->taskMap[$task];

        } elseif (isset($this->taskMap['__default'])) {
            $doTask = $this->taskMap['__default'];

        } else {
            throw new Exception(Text::sprintf('JLIB_APPLICATION_ERROR_TASK_NOT_FOUND', $task), 404);
        }

        // Record the actual task being fired
        $this->doTask = $doTask;

        $result = $this->$doTask();

        // Runs the event after the task was executed
        $eventParams[] = &$result;
        Factory::getApplication()->triggerEvent('osmapOnAfterExecuteTask', $eventParams);

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function checkToken($method = 'post', $redirect = true)
    {
        if (is_callable([parent::class, 'checkToken'])) {
            return parent::checkToken($method, $redirect);
        }

        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

        return true;
    }
}
