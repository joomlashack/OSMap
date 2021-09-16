<?php
/**
 * @package   OSMap
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2007-2014 XMap - Joomla! Vargas - Guillermo Vargas. All rights reserved.
 * @copyright 2016-2021 Joomlashack.com. All rights reserved.
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

use Alledia\OSMap\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;
use Joomla\Registry\Registry;

defined('_JEXEC') or die();

require_once 'TraitOsmapField.php';

FormHelper::loadFieldClass('List');

class OsmapFormFieldMenus extends JFormFieldList
{
    use TraitOsmapField;

    /**
     * @inheritdoc
     */
    public $type = 'osmapmenus';

    /**
     * @inheritDoc
     * @throws Exception
     */
    protected function getOptions()
    {
        $db = Factory::getDbo();

        // Get the list of menus from the database
        $query = $db->getQuery(true)
            ->select('id AS value')
            ->select('title AS text')
            ->from('#__menu_types AS menus')
            ->order('menus.title');

        $menus = $db->setQuery($query)->loadObjectList('value');

        // Add the sitemap menus in the defined order to the list
        $options      = [];
        $currentMenus = [];
        foreach ($menus as $menuId => $menu) {
            if (!in_array($menuId, $currentMenus)) {
                $options[] = $menu;
            }
        }

        return array_merge(parent::getOptions(), $options);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    protected function getInput()
    {
        $disabled   = ($this->element['disabled'] == 'true');
        $readonly   = ($this->element['readonly'] == 'true');
        $attributes = ' ';

        $type = 'radio';

        if ($v = $this->element['size']) {
            $attributes .= 'size="' . $v . '" ';
        }

        if ($v = $this->element['class']) {
            $attributes .= 'class="' . $v . '" ';
        } else {
            $attributes .= 'class="inputbox" ';
        }

        if ($this->element['multiple']) {
            $type = 'checkbox';
        }

        $value = $this->value;
        if (!is_array($value)) {
            // Convert the selections field to an array.
            $registry = new Registry();
            $registry->loadString($value);
            $value = $registry->toArray();
        }

        $this->inputId = 'menus';

        if (Version::MAJOR_VERSION < 4) {
            HTMLHelper::_('jquery.ui', ['core', 'sortable']);
            HTMLHelper::_('script', 'jui/sortablelist.js', false, true);
            HTMLHelper::_('stylesheet', 'jui/sortablelist.css', false, true, false);

            $script = <<<JSCRIPT
;(function ($){
    $(document).ready(function (){
        $('#ul_{$this->inputId}').sortable({
            'appendTo': document.body
        });

        // Toggle checkbox clicking on the line
        $('#ul_{$this->inputId} li').on('click', function(event) {
            if ($(event.srcElement).hasClass('osmap-menu-item')
                || $(event.srcElement).hasClass('control-label')
                || $(event.srcElement).hasClass('osmap-menu-options')) {

                $(this).children('input').click();
            }
        });
    });
})(jQuery);
JSCRIPT;

            Factory::getDocument()->addScriptDeclaration($script);
        }

        if ($disabled || $readonly) {
            $attributes .= 'disabled="disabled"';
        }
        $options = $this->getOptions();

        $textSelected         = Text::_('COM_OSMAP_SELECTED_LABEL');
        $textTitle            = Text::_('COM_OSMAP_TITLE_LABEL');
        $textChangePriority   = Text::_('COM_OSMAP_PRIORITY_LABEL');
        $textChangeChangeFreq = Text::_('COM_OSMAP_CHANGE_FREQUENCY_LABEL');

        $return = <<<HTML
            <div class="osmap-table">
                <div class="osmap-list-header">
                    <div class="osmap-cell osmap-col-selected">{$textSelected}</div>
                    <div class="osmap-cell osmap-col-title">{$textTitle}</div>
                    <div class="osmap-cell osmap-col-priority">{$textChangePriority}</div>
                    <div class="osmap-cell osmap-col-changefreq">{$textChangeChangeFreq}</div>
                </div>
HTML;

        $return .= '<ul id="ul_' . $this->inputId . '" class="ul_sortable">';

        // Create a regular list.
        $i = 0;

        // Show the enabled menus first
        $this->currentItems = array_keys($value);
        uasort($options, [$this, 'myCompare']);

        foreach ($options as $option) {
            $prioritiesName        = preg_replace('/(jform\[[^]]+)(].*)/', '$1_priority$2', $this->name);
            $changeFrequencyName   = preg_replace('/(jform\[[^]]+)(].*)/', '$1_changefreq$2', $this->name);
            $selected              = (isset($value[$option->value]) ? ' checked="checked"' : '');
            $changePriorityField   = HTMLHelper::_(
                'osmap.priorities',
                $prioritiesName,
                ($selected ? $value[$option->value]['priority'] : '0.5'),
                $i
            );
            $changeChangeFreqField = HTMLHelper::_(
                'osmap.changefrequency',
                $changeFrequencyName,
                ($selected ? $value[$option->value]['changefreq'] : 'weekly'),
                $i
            );

            $i++;

            $return .= <<<HTML
                <li id="menu_{$option->value}" class="osmap-menu-item">
                    <div class="osmap-cell osmap-col-selected" data-title="{$textSelected}">
                        <input type="{$type}" 
                               id="{$this->id}_{$i}"
                               name="{$this->name}"
                               value="{$option->value}" {$attributes} {$selected}/>
                    </div>
                    <div class="osmap-cell osmap-col-title" data-title="{$textTitle}">
                        <label for="{$this->id}_{$i}" class="menu_label">{$option->text}</label>
                    </div>

                    <div class="osmap-cell osmap-col-priority osmap-menu-options" data-title="{$textChangePriority}">
                        <div class="controls">{$changePriorityField}</div>
                    </div>

                    <div class="osmap-cell osmap-col-changefreq osmap-menu-options"
                         data-title="{$textChangeChangeFreq}">
                        <div class="controls">{$changeChangeFreqField}</div>
                    </div>
                </li>
HTML;
        }

        $return .= '</ul></div>';

        return $return;
    }

    /**
     * @param object $a
     * @param object $b
     *
     * @return int
     */
    public function myCompare(object $a, object $b): int
    {
        $indexA = array_search($a->value, $this->currentItems);
        $indexB = array_search($b->value, $this->currentItems);

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
