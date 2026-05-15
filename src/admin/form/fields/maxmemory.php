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
use Alledia\Framework\Joomla\Form\Field\ListField;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die();

require_once JPATH_ADMINISTRATOR . '/components/com_osmap/include.php';
require_once 'TraitOsmapField.php';
// phpcs:enable PSR1.Files.SideEffects
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

class OsmapFormFieldMaxmemory extends ListField
{
    use TraitOsmapField;

    /**
     * @var array
     */
    protected static array $magnitudes = [
        'K' => 1024,
        'M' => 1024 * 1024,
        'G' => 1024 * 1024 * 1024,
    ];

    /**
     * @inheritDoc
     */
    protected function getInput()
    {
        return parent::getInput() . $this->getCurrentMemory();
    }

    protected function getCurrentMemory()
    {
        $limit = $limitBytes = ini_get('memory_limit');

        $regex = sprintf('/(\d*)([%s])/', join(array_keys(static::$magnitudes)));
        if (preg_match($regex, $limitBytes, $match)) {
            $limitBytes = $match[1] * static::$magnitudes[$match[2]];
        }
        $limitBytes = $limitBytes > 0 ? number_format($limitBytes) : Text::_('COM_OSMAP_OPTION_UNLIMITED');

        return '<small class="form-text">'
            . Text::sprintf('COM_OSMAP_MAX_MEMORY_CURRENT', $limit, $limitBytes)
            . '</small>';
    }
}
