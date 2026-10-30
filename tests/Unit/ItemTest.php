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

use Alledia\OSMap\Router;
use Alledia\OSMap\Sitemap\Item;
use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;
use ReflectionClass;
use ReflectionMethod;
use Tests\Support\Fixtures\NullDateDatabase;
use Tests\Support\Fixtures\SiteUri;
use Tests\Support\Fixtures\TestFactory;

class ItemTest extends Unit
{
    protected function setUp(): void
    {
        parent::setUp();

        SiteUri::setSite('https://www.example.com');
        TestFactory::setPimpleContainer([
            'db'     => new NullDateDatabase(),
            'router' => new Router(),
        ]);
    }

    protected function tearDown(): void
    {
        SiteUri::reset();

        parent::tearDown();
    }

    /**
     * The constructor builds the item from a Joomla menu item, so tests start
     * from an empty item and set only the properties they need.
     *
     * @param array $properties
     *
     * @return Item
     */
    protected function createItem(array $properties = []): Item
    {
        $item = (new ReflectionClass(Item::class))->newInstanceWithoutConstructor();

        foreach ($properties as $name => $value) {
            $item->{$name} = $value;
        }

        return $item;
    }

    /**
     * @param Item   $item
     * @param string $method
     *
     * @return mixed
     */
    protected function callProtected(Item $item, string $method)
    {
        return (new ReflectionMethod($item, $method))->invoke($item);
    }

    public function testCalculateUID()
    {
        $item = $this->createItem(['fullLink' => 'https://www.example.com/blog']);

        $item->calculateUID(false, 'menuitem.');
        $this->assertSame('menuitem.' . md5('https://www.example.com/blog'), $item->uid);

        $item->fullLink = 'https://www.example.com/other';
        $item->calculateUID();
        $this->assertSame('menuitem.' . md5('https://www.example.com/blog'), $item->uid, 'An existing UID is kept');

        $item->calculateUID(true);
        $this->assertSame(md5('https://www.example.com/other'), $item->uid, 'Forcing replaces the UID');
    }

    public function testGetAdminNotesString()
    {
        $this->assertSame('', $this->createItem()->getAdminNotesString());

        $item = $this->createItem(['adminNotes' => ['First note', 'Second note']]);
        $this->assertSame("First note\nSecond note", $item->getAdminNotesString());
    }

    public static function componentProvider(): array
    {
        return [
            'non-SEF link'       => ['index.php?option=com_content&view=article&id=1', 'com_content'],
            'option not first'   => ['index.php?Itemid=101&option=com_contact&view=contact', 'com_contact'],
            'leading slash'      => ['/index.php?option=com_tags', 'com_tags'],
            'no option'          => ['index.php?Itemid=101', null],
            'absolute url'       => ['https://www.example.com/index.php?option=com_content', null],
            'not a component'    => ['index.php?option=mod_menu', null],
        ];
    }

    #[DataProvider('componentProvider')]
    public function testExtractComponentFromLink(string $link, ?string $expected)
    {
        $item = $this->createItem(['link' => $link]);

        $this->callProtected($item, 'extractComponentFromLink');

        $this->assertSame($expected, $item->component);
    }

    public static function internalLinkProvider(): array
    {
        return [
            'internal link'       => ['index.php?option=com_content', 'component', true],
            'external link'       => ['https://www.other.com/', 'url', false],
            'external separator'  => ['https://www.other.com/', 'separator', true],
            'external heading'    => ['https://www.other.com/', 'heading', true],
        ];
    }

    #[DataProvider('internalLinkProvider')]
    public function testCheckLinkIsInternal(string $link, string $type, bool $expected)
    {
        $item = $this->createItem(['link' => $link, 'type' => $type]);

        $this->assertSame($expected, $this->callProtected($item, 'checkLinkIsInternal'));
    }

    public static function dateProvider(): array
    {
        return [
            'date'          => ['2024-05-01 10:30:00', '2024-05-01T10:30:00+00:00'],
            'timestamp'     => [1714559400, '2024-05-01T10:30:00+00:00'],
            'null date'     => ['0000-00-00 00:00:00', null],
            'empty'         => ['', null],
            'null'          => [null, null],
            'negative'      => [-86400, null],
        ];
    }

    #[DataProvider('dateProvider')]
    public function testPrepareDate($date, ?string $expected)
    {
        $item = $this->createItem(['created' => $date]);

        $item->prepareDate('created');

        $this->assertSame($expected, $item->created);
    }

    public function testPrepareDateUsesDefaultForEmptyDate()
    {
        $item = $this->createItem(['publishUp' => null]);

        $item->prepareDate('publishUp', '2024-05-01 10:30:00');

        $this->assertSame('2024-05-01T10:30:00+00:00', $item->publishUp);
    }

    #[DataProvider('dateProvider')]
    public function testSetModificationDate($date, ?string $expected)
    {
        $item = $this->createItem(['modified' => $date]);

        $item->setModificationDate();

        $this->assertSame($expected, $item->modified);
    }
}
