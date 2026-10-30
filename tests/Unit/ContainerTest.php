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

use Alledia\OSMap\Container;
use PHPUnit\Framework\TestCase;
use stdClass;

class ContainerTest extends TestCase
{
    public function testMagicGetResolvesServices()
    {
        $service   = new stdClass();
        $container = new Container([
            'router' => function () use ($service) {
                return $service;
            },
        ]);

        $this->assertSame($service, $container->router);
        $this->assertSame($service, $container->router, 'Services are shared');
    }

    public function testUnknownServiceIsNull()
    {
        $this->assertNull((new Container())->noSuchService);
    }

    public function testMagicGetterResolvesServices()
    {
        $container = new Container(['router' => 'router service']);

        $this->assertSame('router service', $container->getRouter());
        $this->assertNull($container->getNoSuchService());
    }

    public function testMagicCallIgnoresOtherMethods()
    {
        $container = new Container(['router' => 'router service']);

        $this->assertNull($container->router(), 'Only get*() resolves a service');
        $this->assertNull($container->getRouter('argument'), 'Getters take no arguments');
    }
}
