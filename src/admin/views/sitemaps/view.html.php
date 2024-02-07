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
use Alledia\OSMap\Helper\General;
use Alledia\OSMap\View\Admin\AbstractList;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\HTML\Helpers\Sidebar;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Toolbar\ToolbarHelper;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die();
// phpcs:enable PSR1.Files.SideEffects
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

class OSMapViewSitemaps extends AbstractList
{
    /**
     * @var string[]
     */
    protected $languages = null;

    /**
     * @var object
     */
    protected $item = null;

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function display($tpl = null)
    {
        $this->model = $this->getModel();

        $this->items         = $this->model->getItems();
        $this->state         = $this->model->getState();
        $this->filterForm    = $this->model->getFilterForm();
        $this->activeFilters = $this->model->getActiveFilters();

        // We don't need toolbar or submenus in the modal window
        if (stripos($this->getLayout(), 'modal') !== 0) {
            $this->setToolbar();
            $this->sidebar = Sidebar::render();
        }

        // Get the active languages for multi-language sites
        $this->languages = null;
        if (Multilanguage::isEnabled()) {
            $this->languages = LanguageHelper::getLanguages();
        }

        parent::display($tpl);
    }

    /**
     * @inheritDoc
     */
    protected function setToolbar()
    {
        $this->setTitle('COM_OSMAP_SUBMENU_SITEMAPS');

        General::addSubmenu('sitemaps');

        ToolbarHelper::addNew('sitemap.add');
        ToolbarHelper::custom('sitemap.edit', 'edit.png', 'edit_f2.png', 'JTOOLBAR_EDIT', true);
        ToolbarHelper::custom('sitemaps.publish', 'publish.png', 'publish_f2.png', 'JTOOLBAR_Publish', true);
        ToolbarHelper::custom('sitemaps.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
        ToolbarHelper::custom(
            'sitemap.setAsDefault',
            'featured.png',
            'featured_f2.png',
            'COM_OSMAP_TOOLBAR_SET_DEFAULT',
            true
        );

        if ($this->state->get('filter.published') == -2) {
            ToolbarHelper::deleteList('', 'sitemaps.delete');
        } else {
            ToolbarHelper::trash('sitemaps.trash');
        }

        parent::setToolBar();
    }

    /**
     * @param object  $item
     * @param string  $type
     * @param ?string $lang
     *
     * @return string
     * @throws Exception
     */
    protected function getLink(object $item, string $type, ?string $lang = null): string
    {
        $view   = in_array($type, ['news', 'images']) ? 'xml' : $type;
        $menuId = $item->menuIdList[$view] ?? null;

        $query = [
            'option' => 'com_osmap',
        ];

        if ($menuId) {
            $query['Itemid'] = $menuId;
        }

        if (empty($query['Itemid'])) {
            $query = array_merge(
                $query,
                [
                    'view' => $view,
                    'id'   => $item->id
                ]
            );
        }

        if ($type != $view) {
            $query[$type] = 1;
        }
        if ($view == 'xml') {
            $menu     = CMSApplication::getInstance('site')->getMenu()->getItem($menuId);
            $menuView = empty($menu->query['view']) ? null : $menu->query['view'];

            if ($view != $menuView) {
                $query['format'] = 'xml';
            }
        }

        if ($lang) {
            $query['lang'] = $lang;
        }

        return Factory::getPimpleContainer()
            ->router->routeURL('index.php?' . http_build_query($query));
    }
}
