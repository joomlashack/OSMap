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

use Alledia\Framework\Joomla\Form\Field\TraitLayouts;
use Alledia\OSMap\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\Registry\Registry;

defined('_JEXEC') or die();

require_once 'TraitOsmapField.php';

class OsmapFormFieldMenus extends FormField
{
    use TraitOsmapField;
    use TraitLayouts;

    /**
     * @inheritdoc
     */
    public $type = 'osmapmenus';

    /**
     * @inheritdoc
     */
    protected $layout = 'osmap.menus';

    /**
     * @inheritDoc
     */
    protected function getInput()
    {
        if (!is_array($this->value)) {
            // Ensure value is an array
            $registry    = new Registry($this->value);
            $this->value = $registry->toArray();
        }

        return parent::getInput();
    }

    /**
     * @inheritDoc
     */
    protected function getLayoutData()
    {
        return array_merge(
            parent::getLayoutData(),
            [
                'options' => $this->getOptions()
            ]
        );
    }

    /**
     * @inheritDoc
     */
    protected function getLabel()
    {
        return '';
    }

    /**
     * @return object[]
     * @throws Exception
     */
    protected function getOptions()
    {
        $db = Factory::getDbo();

        // Get the list of menus from the database
        $query = $db->getQuery(true)
            ->select([
                'id AS value',
                'title AS text'
            ])
            ->from('#__menu_types AS menus')
            ->order('menus.title');

        $options = $db->setQuery($query)->loadObjectList();

        uasort($options, [$this, 'sortOptions']);

        return $options;
    }

    /**
     * @param object $a
     * @param object $b
     *
     * @return int
     */
    public function sortOptions(object $a, object $b): int
    {
        $indexA = array_search($a->value, array_keys($this->value));
        $indexB = array_search($b->value, array_keys($this->value));

        if ($indexA === $indexB && $indexA !== false) {
            return 0;
        }

        if ($indexA === false && $indexA === $indexB) {
            return ($a->value < $b->value) ? -1 : 1;
        }

        if ($indexA === false) {
            return 1;
        }

        if ($indexB === false) {
            return -1;
        }

        return ($indexA < $indexB) ? -1 : 1;
    }
}
