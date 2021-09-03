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

use Joomla\CMS\Version;
use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die();

jimport('joomla.form.field');

/**
 * Supports a modal sitemap picker.
 *
 * @package             OSMap
 * @subpackage          com_osmap
 * @since               2.0
 */
class JFormFieldSitemaps extends JFormField
{
    /**
     * The field type.
     *
     * @var    string
     */
    protected $type = 'Sitemaps';

    /**
     * Method to get a list of options for a sitemaps list input.
     *
     * @return    array        An array of JHtml options.
     */
    protected function getInput()
    {
        // Initialise variables.
        $db  = JFactory::getDBO();
        $doc = JFactory::getDocument();

        HTMLHelper::_('bootstrap.renderModal', 'a.btn-modal');

        // Get the name of the linked chart
        if ($this->value) {
            $query = $db->getQuery(true)
                ->select('name')
                ->from('#__osmap_sitemaps')
                ->where('id = ' . (int)$this->value);
            $name = $db->setQuery($query)->loadResult();
        } else {
            $name = '';
        }

        if (empty($name)) {
            $name = JText::_('COM_OSMAP_SELECT_AN_SITEMAP');
        }



        $link = 'index.php?option=com_osmap&amp;view=sitemaps&amp;layout=modal&amp;tmpl=component&amp;function=jSelectSitemap_' . $this->id;

        if (Version::MAJOR_VERSION < 4) {
            JHtml:_('behavior.framework');
            // Load the modal behavior.
            JHtml::_('behavior.modal', 'a.btn-modal');

            $doc->addScriptDeclaration(
                "function jSelectSitemap_" . $this->id . "(id, name, object) {
                   $('" . $this->id . "_id').value = id;
                   $('" . $this->id . "_name').value = name;
                   SqueezeBox.close();
              }"
            );

            $html = '<span class="input-append">' . "\n";
            $html .= '<input class="input-medium" type="text" id="' . $this->id . '_name" value="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" disabled="disabled" />';
            $html .= '<a class="btn-modal btn" title="' . JText::_('COM_OSMAP_CHANGE_SITEMAP') . '"  href="' . $link . '" rel="{handler: \'iframe\', size: {x: 800, y: 450}}"><i class="icon-file"></i> ' . JText::_('COM_OSMAP_CHANGE_SITEMAP_BUTTON') . '</a>' . "\n";
            $html .= '</span>' . "\n";
            $html .= '<input type="hidden" id="' . $this->id . '_id" name="' . $this->name . '" value="' . (int) $this->value . '" />';
        } else {
            HTMLHelper::_('jquery.framework');

            $modalId = 'ModalSelectSitemapModal_' . $this->id;

            $html = '<div class="input-group">' . "\n";
            $html .= '<input class="form-control" type="text" id="' . $this->id . '_name" value="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" disabled="disabled" />';
            $html .= '<a class="btn btn-secondary btn-modal" data-bs-toggle="modal" data-bs-target="#' . $modalId . '" title="' . JText::_('COM_OSMAP_CHANGE_SITEMAP') . '"  href="' . $link . '"><i class="icon-file"></i> ' . JText::_('COM_OSMAP_CHANGE_SITEMAP_BUTTON') . '</a>' . "\n";
            $html .= '</div>' . "\n";
            $html .= '<input type="hidden" id="' . $this->id . '_id" name="' . $this->name . '" value="' . (int) $this->value . '" />';

            $html .= HTMLHelper::_(
                'bootstrap.renderModal',
                $modalId,
                array(
                    'title'       => JText::_('COM_OSMAP_CHANGE_SITEMAP'),
                    'url'         => $link,
                    'height'      => '400px',
                    'width'       => '800px',
                    'bodyHeight'  => 70,
                    'modalWidth'  => 80,
                    'footer'      => '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">'
                        . JText::_('JLIB_HTML_BEHAVIOR_CLOSE') . '</button>',
                )
            );

            $doc->addScriptDeclaration(
                "
                function jSelectSitemap_" . $this->id . "(id, name, object) {
                   $('#" . $this->id . "_id').val(id);
                   $('#" . $this->id . "_name').val(name);
                   $('[data-bs-dismiss=\"modal\"]').trigger('click');
              }"
            );
        }

        return $html;
    }
}
