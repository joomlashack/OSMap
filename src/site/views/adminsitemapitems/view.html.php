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

use Alledia\OSMap\Component\Helper as ComponentHelper;
use Alledia\OSMap\Factory;
use Alledia\OSMap\Sitemap\SitemapInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\Input\Input;
use Joomla\Registry\Registry;

defined('_JEXEC') or die();

class OSMapViewAdminSitemapItems extends HtmlView
{
    /**
     * @var Registry
     */
    protected $params = null;

    /**
     * @var SitemapInterface
     */
    protected $sitemap = null;

    /**
     * @var Registry
     */
    protected $osmapParams = null;

    /**
     * @var string
     */
    protected $message = null;

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function display($tpl = null)
    {
        $this->checkAccess();

        $container = Factory::getPimpleContainer();

        try {
            $id = $container->input->getInt('id');

            $this->params = Factory::getApplication()->getParams();

            // Load the sitemap instance
            $this->sitemap     = Factory::getSitemap($id);
            $this->osmapParams = ComponentHelper::getParams();

        } catch (Exception $e) {
            $this->message = $e->getMessage();
        }

        parent::display($tpl);
    }

    /**
     * This view should only be available from the backend
     *
     * @return void
     * @throws Exception
     */
    protected function checkAccess()
    {
        $server  = new Input(array_change_key_case($_SERVER, CASE_LOWER));
        $referer = parse_url($server->getString('http_referer'));

        if (empty($referer['query']) == false) {
            parse_str($referer['query'], $query);

            $option = empty($query['option']) ? null : $query['option'];
            $view   = empty($query['view']) ? null : $query['view'];

            if ($option == 'com_osmap' && $view == 'sitemapitems') {
                // Good enough
                return;
            }
        }

        throw new Exception(Text::_('JERROR_PAGE_NOT_FOUND'), 404);
    }
}
