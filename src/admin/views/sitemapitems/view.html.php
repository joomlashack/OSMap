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
use Alledia\OSMap\Sitemap\Standard;
use Alledia\OSMap\View\Admin\AbstractList;
use Joomla\CMS\Toolbar\ToolbarHelper;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die();
// phpcs:enable PSR1.Files.SideEffects
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

class OSMapViewSitemapItems extends AbstractList
{
    /**
     * @var Standard
     */
    protected $sitemap = null;

    /**
     * @var string
     */
    protected $language = null;

    /**
     * @inheritDoc
     */
    public function display($tpl = null)
    {
        $sitemapId = $this->app->input->getInt('id', 0);

        $this->sitemap  = Factory::getSitemap($sitemapId);
        $this->language = $this->app->input->get('lang', '');

        $this->setToolBar();

        parent::display($tpl);
    }

    /**
     * @inheritDoc
     */
    protected function setToolBar($addDivider = true)
    {
        $this->setTitle('COM_OSMAP_PAGE_VIEW_SITEMAP_ITEMS');

        ToolbarHelper::apply('sitemapitems.apply');
        ToolbarHelper::save('sitemapitems.save');

        ToolbarHelper::cancel('sitemapitems.cancel');

        parent::setToolBar();
    }
}
