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

use Alledia\OSMap\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

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

    /**
     * @inheritDoc
     */
    protected function getInput()
    {
        if ($value = $this->value ?: '') {
            $db = Factory::getDbo();
            $db->setQuery(
                $db->getQuery(true)
                    ->select('name')
                    ->from('#__osmap_sitemaps')
                    ->where('id = ' . (int)$value)
            );
            $selectedName = $db->loadResult();
        }

        if (empty($selectedName)) {
            $selectedName = Text::_('COM_OSMAP_OPTION_SELECT_SITEMAP');
        }

        $function = 'osmapSelectSitemap_' . $this->id;

        $linkQuery = [
            'option'   => 'com_osmap',
            'view'     => 'sitemaps',
            'layout'   => 'modal',
            'tmpl'     => 'component',
            'function' => $function
        ];

        $link = 'index.php?' . htmlspecialchars(http_build_query($linkQuery));

        return HTMLHelper::_(
            'alledia.renderModal',
            [
                'id'       => $this->id,
                'name'     => $this->name,
                'link'     => $link,
                'function' => $function,
                'itemType' => 'Sitemap',
                'title'    => Text::_('COM_OSMAP_OPTION_SELECT_SITEMAP'),
                'hint'     => $selectedName,
                'value'    => $value,
                'required' => $this->required,
            ]
        );
    }

    /**
     * @inheritDoc
     */
    protected function getLabel()
    {
        return str_replace($this->id, $this->id . '_id', parent::getLabel());
    }
}
