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
use Alledia\OSMap\Sitemap\Item;
use Alledia\OSMap\Sitemap\Standard;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die();
// phpcs:enable PSR1.Files.SideEffects
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

class OsmapViewXml extends HtmlView
{
    /**
     * @var SiteApplication
     */
    protected $app = null;

    /**
     * @var string
     */
    protected $type = null;

    /**
     * @var Registry
     */
    protected $params = null;

    /**
     * @var Registry
     */
    protected $osmapParams = null;

    /**
     * @var Standard
     */
    protected $sitemap = null;

    /**
     * @var string
     */
    protected $language = null;

    /**
     * @var DateTime
     */
    protected $newsCutoff = null;

    /**
     * @var int
     */
    protected $newsLimit = 1000;

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
        if ($document->getType() != 'xml') {
            // There are ways to get here with a non-xml document, so we have to redirect
            $uri = Uri::getInstance();
            $uri->setVar('format', 'xml');

            $this->app->redirect($uri);

            // Not strictly necessary, but makes the point :)
            return;
        }

        $this->type    = General::getSitemapTypeFromInput();
        $this->sitemap = Factory::getSitemap($this->app->input->getInt('id'), $this->type);
        if (!$this->sitemap->isPublished) {
            throw new Exception(Text::_('COM_OSMAP_MSG_SITEMAP_IS_UNPUBLISHED'), 404);
        }

        $this->params      = $this->app->getParams();
        $this->osmapParams = ComponentHelper::getParams('com_osmap');
        $this->language    = $document->getLanguage();
        $this->newsCutoff  = new DateTime('-' . $this->sitemap->newsDateLimit . ' days');

        if ($this->params->get('debug', 0)) {
            $document->setMimeEncoding('text/plain');
        }

        parent::display($tpl);
    }

    /**
     * @return string
     */
    protected function addStylesheet(): string
    {
        if ($this->params->get('add_styling', 1)) {
            $query = [
                'option' => 'com_osmap',
                'view'   => 'xsl',
                'format' => 'xsl',
                'layout' => $this->type,
                'id'     => $this->sitemap->id,
            ];
            if ($itemId = $this->app->input->getInt('Itemid')) {
                $query['Itemid'] = $itemId;
            }

            return sprintf(
                '<?xml-stylesheet type="text/xsl" href="%s"?>',
                Route::_('index.php?' . http_build_query($query))
            );
        }

        return '';
    }

    /**
     * @param Item $node
     *
     * @return ?DateTime
     */
    protected function isNewsPublication(Item $node): ?DateTime
    {
        try {
            $publicationDate = (
                !empty($node->publishUp)
                && $node->publishUp != Factory::getDbo()->getNullDate()
                && $node->publishUp != -1
            ) ? $node->publishUp : null;

            if ($publicationDate) {
                $publicationDate = new DateTime($publicationDate);

                if ($this->newsCutoff <= $publicationDate) {
                    $this->newsLimit--;
                    if ($this->newsLimit >= 0) {
                        return $publicationDate;
                    }
                }
            }

        } catch (Throwable $error) {
            // Don't care
        }

        return null;
    }
}
