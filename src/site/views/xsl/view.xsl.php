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
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die();
// phpcs:enable PSR1.Files.SideEffects
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

class OsmapViewXsl extends HtmlView
{
    /**
     * @var SiteApplication
     */
    protected $app = null;

    /**
     * @var string
     */
    protected $pageHeading = null;

    /**
     * @var string
     */
    protected $pageTitle = null;

    /**
     * @var string
     */
    protected $language = null;

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->app = Factory::getApplication();
    }

    /**
     * @inheritDoc
     */
    public function display($tpl = null)
    {
        $document = $this->app->getDocument();

        $this->language = $document->getLanguage();

        $menu    = $this->app->getMenu()->getActive();
        $isOsmap = $menu && $menu->query['option'] == 'com_osmap';
        $params  = $this->app->getParams();
        $type    = General::getSitemapTypeFromInput();
        $sitemap = Factory::getSitemap($this->app->input->getInt('id'), $type);

        $title = $params->get('page_title', '');
        if ($isOsmap == false) {
            $title = $sitemap->name ?: $title;
        }

        if (empty($title)) {
            $title = $this->app->get('sitename');

        } elseif ($this->app->get('sitename_pagetitles', 0) == 1) {
            $title = Text::sprintf('JPAGETITLE', $this->app->get('sitename'), $title);

        } elseif ($this->app->get('sitename_pagetitles', 0) == 2) {
            $title = JText::sprintf('JPAGETITLE', $title, $this->app->get('sitename'));
        }
        $this->pageTitle = $this->escape($title);
        if ($isOsmap && $params->get('show_page_heading')) {
            $this->pageHeading = $this->escape($params->get('page_heading') ?: $sitemap->name);
        }

        // We're going to cheat Joomla here because some referenced urls MUST remain http/insecure
        header(sprintf('Content-Type: text/xsl; charset="%s"', $this->_charset));
        header('Content-Disposition: inline');

        parent::display($tpl);

        jexit();
    }
}
