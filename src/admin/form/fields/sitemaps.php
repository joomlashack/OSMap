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
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;

defined('_JEXEC') or die();

require_once 'TraitOsmapField.php';

class OsmapFormFieldSitemaps extends FormField
{
    use TraitOsmapField;

    /**
     * @inheritdoc
     */
    protected $type = 'Sitemaps';

    /**
     * @var string
     */
    protected static $scripts = [];

    protected function getInput()
    {
        HTMLHelper::_('jquery.framework');
        HTMLHelper::_('script', 'system/modal-fields.js', ['version' => 'auto', 'relative' => true]);

        $function = 'osmapSelectSitemap_' . $this->id;

        // Build the script.
        if (empty(static::$scripts[$this->id])) {
            $script = <<<JSCRIPT
window.{$function} = function(id, name) {
    window.processModalSelect('Sitemap', '{$this->id}', id, name);
}
JSCRIPT;

            Factory::getDocument()->addScriptDeclaration($script);

            static::$scripts[$this->id] = true;
        }

        $app     = Factory::getApplication();
        $context = sprintf('%s.%s', $app->input->getCmd('option'), $app->input->getCmd('view'));

        // Build the link
        $linkQuery = [
            'option'   => 'com_osmap',
            'view'     => 'sitemaps',
            'layout'   => 'modal',
            'tmpl'     => 'component',
            'context'  => $context,
            'function' => $function
        ];

        $link = 'index.php?' . htmlspecialchars(http_build_query($linkQuery));

        // Get sitemap title if one selected
        $value = (int)$this->value ?: '';
        if ($value) {
            $db = Factory::getDbo();
            $db->setQuery(
                $db->getQuery(true)
                    ->select('name')
                    ->from('#__osmap_sitemaps')
                    ->where('id = ' . (int)$this->value)
            );
            $title = $db->loadResult();
        }

        if (empty($title)) {
            $title = Text::_('COM_OSMAP_OPTION_SELECT_SITEMAP');
        }

        $title   = htmlspecialchars($title, ENT_QUOTES);
        $modalId = 'ModalSelectSitemap_' . $this->id;

        // Begin field output
        $html = '<span class="input-append">';

        // Display the read-only name field
        $html .= sprintf(
            '<input %s/>',
            ArrayHelper::toString([
                'type'     => 'text',
                'id'       => $this->id . '_name',
                'value'    => $title,
                'class'    => 'input-medium',
                'disabled' => 'disabled',
                'size'     => 35
            ])
        );

        // Create read-only ID field
        $attribs = [
            'type'          => 'hidden',
            'id'            => $this->id . '_id',
            'name'          => $this->name,
            'value'         => $value,
            'data-required' => (int)$this->required
        ];
        if ($this->required) {
            $attribs['class'] = 'class="required modal-value';
        }
        $html .= sprintf('<input  %s/>', ArrayHelper::toString($attribs));

        // Select button
        $html .= HTMLHelper::_(
            'link',
            '#' . $modalId,
            '<span class="icon-list" aria-hidden="true"></span> ' . Text::_('JSELECT'),
            [
                'class'       => 'btn btn-primary hasTooltip',
                'id'          => $this->id . '_change',
                'data-toggle' => 'modal',
                'role'        => 'button',
                'title'       => HTMLHelper::tooltipText(Text::_('COM_OSMAP_OPTION_SELECT_SITEMAP')),
            ]
        );

        // Modal Sitemap window
        $html .= HTMLHelper::_(
            'bootstrap.renderModal',
            $modalId,
            [
                'title'      => Text::_('COM_OSMAP_OPTION_SELECT_SITEMAP'),
                'url'        => $link,
                'height'     => '400px',
                'width'      => '800px',
                'bodyHeight' => '70',
                'modalWidth' => '80',
                'footer'     => sprintf(
                    '<a role="button" class="btn" data-dismiss="modal" aria-hidden="true">%s</a>',
                    Text::_('JLIB_HTML_BEHAVIOR_CLOSE')
                ),
            ]
        );

        return $html;
    }
}
