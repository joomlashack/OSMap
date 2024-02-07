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

use Alledia\OSMap\Controller\Form;
use Alledia\OSMap\Factory;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die();
// phpcs:enable PSR1.Files.SideEffects
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

class OSMapControllerSitemapItems extends Form
{
    /**
     * @inheritDoc
     */
    public function cancel($key = null)
    {
        $this->setRedirect('index.php?option=com_osmap&view=sitemaps');
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function save($key = null, $urlVar = null)
    {
        $this->checkToken();

        $app = Factory::getApplication();

        $sitemapId  = $app->input->getInt('id');
        $updateData = $app->input->get('update-data', null, 'raw');
        $language   = $app->input->getString('language');

        $model = $this->getModel();

        if ($updateData) {
            $updateData = json_decode($updateData, true);
            if ($updateData && is_array($updateData)) {
                foreach ($updateData as $data) {
                    $row = $model->getTable();
                    $row->load([
                        'sitemap_id'    => $sitemapId,
                        'uid'           => $data['uid'],
                        'settings_hash' => $data['settings_hash'],
                    ]);

                    $data['sitemap_id'] = $sitemapId;
                    $data['format']     = '2';

                    $row->save($data) . ': ' . print_r($data, 1);
                }
            }
        }

        if ($this->getTask() === 'apply') {
            $query = [
                'option' => 'com_osmap',
                'view'   => 'sitemapitems',
                'id'     => $sitemapId,
            ];

            if ($language) {
                $query['lang'] = $language;
            }

            $this->setRedirect('index.php?' . http_build_query($query));

        } else {
            $this->setRedirect('index.php?option=com_osmap&view=sitemaps');
        }
    }
}
