<?php
/**
 * @package   OSMap
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2007-2014 XMap - Joomla! Vargas - Guillermo Vargas. All rights reserved.
 * @copyright 2016-2020 Joomlashack.com. All rights reserved.
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

use Alledia\OSMap\Installer\Script;

defined('_JEXEC') or die();

// Adapt for install and uninstall environments
if (file_exists(__DIR__ . '/admin/library/alledia/osmap/Installer/Script.php')) {
    require_once __DIR__ . '/admin/library/alledia/osmap/Installer/Script.php';
} else {
    require_once __DIR__ . '/library/alledia/osmap/Installer/Script.php';
}

class com_osmapInstallerScript extends Script
{   

	public function postFlight($type, $parent)
    {
        parent::postFlight($type, $parent);

        switch ($type) {
            case 'install':
            case 'discover_install':
            case 'update':
                $this->clearLanguageFiles();
                break;
        }
    }

    /**
     * Remove any language files left in core
     */
	protected function clearLanguageFiles()
    {
        $files = array_merge(
            Folder::files(JPATH_ADMINISTRATOR . '/language', '_osmap', true, true),
            Folder::files(JPATH_SITE . '/language', '_osmap', true, true)
        );

        foreach ($files as $file) {
            @unlink($file);
        }
    }
}
