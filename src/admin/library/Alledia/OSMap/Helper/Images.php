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

namespace Alledia\OSMap\Helper;

use Alledia\OSMap\Factory;
use Exception;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die();
// phpcs:enable PSR1.Files.SideEffects
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace


class Images
{
    /**
     * Extracts images from the given text.
     *
     * @param string $text
     * @param ?int   $max
     *
     * @return array
     * @throws Exception
     */
    public function getImagesFromText(string $text, int $max = 0): array
    {
        $container = Factory::getPimpleContainer();
        $images    = [];

        // Look for <img> tags
        preg_match_all(
            '/<img[^>](?=.*(?:src="(?P<src>[^"]*)"))?(?=.*(title="(?P<title>[^"]*)"))?(?=.*(alt="(?P<alt>[^"]*)"))?.[^>]*>/i',
            $text,
            $matches1,
            PREG_SET_ORDER
        );

        // Look for <a> tags with href to images
        preg_match_all(
            '/<a[^>](?=.*(?:href="(?P<src>[^"]+\.(gif|png|jpg|jpeg))"))(?=.*(title="(?P<title>[^"]*)"))?.[^>]*>/i',
            $text,
            $matches2,
            PREG_SET_ORDER
        );

        $matches = array_merge($matches1, $matches2);

        if (count($matches)) {
            if ($max > 0) {
                $matches = array_slice($matches, 0, $max);
            }

            foreach ($matches as $match) {
                if (
                    ($src = trim($match['src'] ?? ''))
                    && $container->router->isInternalURL($src)
                ) {
                    if ($container->router->isRelativeUri($src)) {
                        $src = $container->router->convertRelativeUriToFullUri($src);
                    }

                    $title = trim($match['title'] ?? '');
                    $alt   = trim($match['alt'] ?? '');

                    $images[] = (object)[
                        'src'   => $src,
                        'title' => $title ?: ($alt ?: '')
                    ];
                }
            }
        }

        return $images;
    }

    /**
     * Return an array of images from the content image params.
     *
     * @param object $item
     *
     * @return array
     * @throws Exception
     */
    public function getImagesFromParams($item)
    {
        $container   = Factory::getPimpleContainer();
        $imagesParam = json_decode($item->images);
        $images      = [];

        if (isset($imagesParam->image_intro) && !empty($imagesParam->image_intro)) {
            $ignoreAlt = $imagesParam->image_intro_alt_empty ?? false;

            $images[] = (object)[
                'src'   => $container->router->convertRelativeUriToFullUri($imagesParam->image_intro),
                'title' => $imagesParam->image_intro_caption
                    ?: ($ignoreAlt ? null : $imagesParam->image_intro_alt)
            ];
        }

        if (isset($imagesParam->image_fulltext) && !empty($imagesParam->image_fulltext)) {
            $ignoreAlt = $imagesParam->image_fulltext_alt_empty ?? true;
            $images[]  = (object)[
                'src'   => $container->router->convertRelativeUriToFullUri($imagesParam->image_fulltext),
                'title' => $imagesParam->image_fulltext_caption
                    ?: ($ignoreAlt ? null : $imagesParam->image_fulltext_all)
            ];
        }

        return $images;
    }
}
