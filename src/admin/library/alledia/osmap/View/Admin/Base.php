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

namespace Alledia\OSMap\View\Admin;

use Alledia\Framework\Joomla\View\Admin\AbstractList;
use Alledia\OSMap\Factory;
use Joomla\CMS\HTML\Helpers\Sidebar;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Toolbar\ToolbarHelper;

defined('_JEXEC') or die();

class Base extends AbstractList
{
    /**
     * @inheritdoc
     */
    protected $state = null;

    /**
     * @inheritDoc
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function display($tpl = null)
    {
        $this->displayHeader();

        $hide    = Factory::getApplication()->input->getBool('hidemainmenu', false);
        $sidebar = count(Sidebar::getEntries()) + count(Sidebar::getFilters());
        if (!$hide && $sidebar > 0) {
            $start = [
                '<div id="j-sidebar-container" class="span2">',
                Sidebar::render(),
                '</div>',
                '<div id="j-main-container" class="span10">'
            ];

        } else {
            $start = ['<div id="j-main-container">'];
        }

        echo join("\n", $start) . "\n";
        parent::display($tpl);
        echo "\n</div>";
    }

    /**
     * Default admin screen title
     *
     * @param ?string $sub
     * @param string  $icon
     *
     * @return void
     */
    protected function setTitle(?string $sub = null, string $icon = 'osmap')
    {
        $img = HTMLHelper::_('image', "com_osmap/icon-48-{$icon}.png", null, null, true, true);
        if ($img) {
            $doc = Factory::getDocument();
            $doc->addStyleDeclaration(".icon-48-{$icon} { background-image: url({$img}); }");
        }

        $title = Text::_('COM_OSMAP');
        if ($sub) {
            $title .= ': ' . Text::_($sub);
        }

        ToolbarHelper::title($title, $icon);
    }

    /**
     * Render the admin screen toolbar buttons
     *
     * @param bool $addDivider
     *
     * @return void
     * @throws \Exception
     */
    protected function setToolBar($addDivider = true)
    {
        $user = Factory::getUser();
        if ($user->authorise('core.admin', 'com_osmap')) {
            if ($addDivider) {
                ToolbarHelper::divider();
            }
            ToolbarHelper::preferences('com_osmap');
        }

        // Prepare the plugins
        PluginHelper::importPlugin('osmap');

        $viewName    = strtolower(str_replace('OSMapView', '', $this->getName()));
        $eventParams = [
            $viewName
        ];
        Factory::getApplication()->triggerEvent('osmapOnAfterSetToolBar', $eventParams);
    }

    /**
     * Render a form fieldset with the ability to compact two fields
     * into a single line
     *
     * @param string $fieldSet
     * @param array  $sameLine
     * @param bool   $tabbed
     *
     * @return string
     */
    protected function renderFieldset(string $fieldSet, array $sameLine = [], ?bool $tabbed = false): string
    {
        $html = [];
        if (!empty($this->form) && $this->form instanceof \JForm) {
            $fieldSets = $this->form->getFieldsets();

            if (!empty($fieldSets[$fieldSet])) {
                $name  = $fieldSets[$fieldSet]->name;
                $label = $fieldSets[$fieldSet]->label;

                $html = [];

                if ($tabbed) {
                    $html[] = HTMLHelper::_('bootstrap.addTab', 'myTab', $name, Text::_($label));
                }

                $html[] = '<div class="row-fluid">';
                $html[] = '<fieldset class="adminform">';

                foreach ($this->form->getFieldset($name) as $field) {
                    if (in_array($field->fieldname, $sameLine)) {
                        continue;
                    }

                    $fieldHtml = [
                        '<div class="control-group">',
                        '<div class="control-label">',
                        $field->label,
                        '</div>',
                        '<div class="controls">',
                        $field->input
                    ];
                    $html      = array_merge($html, $fieldHtml);

                    if (isset($sameLine[$field->fieldname])) {
                        $html[] = ' ' . $this->form->getField($sameLine[$field->fieldname])->input;
                    }

                    $html[] = '</div>';
                    $html[] = '</div>';
                }
                $html[] = '</fieldset>';
                $html[] = '</div>';
                if ($tabbed) {
                    $html[] = HTMLHelper::_('bootstrap.endTab');
                }
            }
        }

        return join("\n", $html);
    }

    /**
     * @inheritDoc
     */
    protected function displayHeader()
    {
        // To be set in subclasses
    }
}
