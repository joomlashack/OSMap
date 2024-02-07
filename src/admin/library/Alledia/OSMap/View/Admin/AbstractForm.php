<?php
/**
 * @package   OSMap
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2021-2024 Joomlashack.com. All rights reserved
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

use Alledia\OSMap\Controller\Form;
use Alledia\OSMap\View\TraitOSMapView;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die();

class AbstractForm extends \Alledia\Framework\Joomla\View\Admin\AbstractForm
{
    use TraitOSMapView;

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
        if ($this->form && $this->form instanceof Form) {
            $fieldSets = $this->form->getFieldsets();

            if ($fieldSets[$fieldSet]) {
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

        return join('', $html);
    }
}
