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
use Alledia\OSMap\View\Admin\AbstractForm;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Toolbar\ToolbarHelper;

defined('_JEXEC') or die();


class OSMapViewSitemap extends AbstractForm
{
    /**
     * @var CMSObject
     */
    protected $item = null;

    /**
     * @inheritDoc
     */
    public function display($tpl = null)
    {
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');

        $this->setToolBar();

        parent::display($tpl);
    }

    /**
     * @param bool $addDivider
     *
     * @return void
     * @throws Exception
     */
    protected function setToolBar()
    {
        $isNew = ($this->item->id == 0);
        Factory::getApplication()->input->set('hidemainmenu', true);

        $title = 'COM_OSMAP_PAGE_VIEW_SITEMAP_' . ($isNew ? 'ADD' : 'EDIT');
        $this->setTitle($title);

        ToolbarHelper::apply('sitemap.apply');
        ToolbarHelper::save('sitemap.save');
        ToolbarHelper::save2new('sitemap.save2new');

        if (!$isNew) {
            ToolbarHelper::save2copy('sitemap.save2copy');
        }

        $alt = $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE';
        ToolbarHelper::cancel('sitemap.cancel', $alt);

        parent::setToolBar();
    }
}
