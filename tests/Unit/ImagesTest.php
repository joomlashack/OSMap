<?php

/**
 * @package   OSMap
 * @contact   www.joomlashack.com, help@joomlashack.com
 * @copyright 2026 Joomlashack.com. All rights reserved
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

namespace Tests\Unit;

use Alledia\OSMap\Helper\Images;
use Alledia\OSMap\Router;
use Codeception\Test\Unit;
use Tests\Support\Fixtures\SiteUri;
use Tests\Support\Fixtures\TestFactory;

class ImagesTest extends Unit
{
    protected function setUp(): void
    {
        parent::setUp();

        SiteUri::setSite('https://www.example.com');
        TestFactory::setPimpleContainer(['router' => new Router()]);
    }

    protected function tearDown(): void
    {
        SiteUri::reset();

        parent::tearDown();
    }

    public function testGetImagesFromText()
    {
        $text = '<p><img src="images/one.jpg" title="One title" alt="One alt"></p>'
            . '<p><img alt="Two alt" src="/images/two.png"></p>'
            . '<p><img src="https://www.example.com/images/three.gif"></p>'
            . '<p><img src="https://cdn.other.com/external.jpg" alt="External"></p>'
            . '<p><a href="images/four.jpeg" title="Four title">Linked image</a></p>'
            . '<p><a href="docs/manual.pdf" title="Not an image">Manual</a></p>';

        $images = (new Images())->getImagesFromText($text);

        $this->assertEquals(
            [
                (object)['src' => 'https://www.example.com/images/one.jpg', 'title' => 'One title'],
                (object)['src' => 'https://www.example.com/images/two.png', 'title' => 'Two alt'],
                (object)['src' => 'https://www.example.com/images/three.gif', 'title' => ''],
                (object)['src' => 'https://www.example.com/images/four.jpeg', 'title' => 'Four title'],
            ],
            $images
        );
    }

    public function testGetImagesFromTextLimitsMatches()
    {
        $text = '<img src="images/one.jpg"><img src="images/two.jpg"><img src="images/three.jpg">';

        $images = (new Images())->getImagesFromText($text, 2);

        $this->assertCount(2, $images);
        $this->assertSame('https://www.example.com/images/two.jpg', $images[1]->src);
    }

    public function testGetImagesFromTextWithoutImages()
    {
        $this->assertSame([], (new Images())->getImagesFromText('<p>No images</p><img alt="No source">'));
    }

    public function testGetImagesFromParams()
    {
        $item = (object)[
            'images' => json_encode([
                'image_intro'              => 'images/intro.jpg',
                'image_intro_alt'          => 'Intro alt',
                'image_intro_alt_empty'    => '',
                'image_intro_caption'      => '',
                'image_fulltext'           => 'images/full.jpg',
                'image_fulltext_alt'       => 'Full alt',
                'image_fulltext_alt_empty' => '',
                'image_fulltext_caption'   => '',
            ]),
        ];

        $this->assertEquals(
            [
                (object)['src' => 'https://www.example.com/images/intro.jpg', 'title' => 'Intro alt'],
                (object)['src' => 'https://www.example.com/images/full.jpg', 'title' => 'Full alt'],
            ],
            (new Images())->getImagesFromParams($item)
        );
    }

    public function testGetImagesFromParamsPrefersCaption()
    {
        $item = (object)[
            'images' => json_encode([
                'image_intro'              => 'images/intro.jpg',
                'image_intro_alt'          => 'Intro alt',
                'image_intro_alt_empty'    => '',
                'image_intro_caption'      => 'Intro caption',
                'image_fulltext'           => 'images/full.jpg',
                'image_fulltext_alt'       => 'Full alt',
                'image_fulltext_alt_empty' => '',
                'image_fulltext_caption'   => 'Full caption',
            ]),
        ];

        $images = (new Images())->getImagesFromParams($item);

        $this->assertSame('Intro caption', $images[0]->title);
        $this->assertSame('Full caption', $images[1]->title);
    }

    public function testGetImagesFromParamsSkipsDecorativeAlt()
    {
        $item = (object)[
            'images' => json_encode([
                'image_intro'              => 'images/intro.jpg',
                'image_intro_alt'          => '',
                'image_intro_alt_empty'    => '1',
                'image_intro_caption'      => '',
                'image_fulltext'           => 'images/full.jpg',
                'image_fulltext_alt'       => '',
                'image_fulltext_alt_empty' => '1',
                'image_fulltext_caption'   => '',
            ]),
        ];

        $images = (new Images())->getImagesFromParams($item);

        $this->assertNull($images[0]->title);
        $this->assertNull($images[1]->title);
    }

    public function testGetImagesFromParamsWithoutImages()
    {
        $item = (object)[
            'images' => json_encode([
                'image_intro'    => '',
                'image_fulltext' => '',
            ]),
        ];

        $this->assertSame([], (new Images())->getImagesFromParams($item));
    }
}
