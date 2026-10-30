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

namespace Tests\Support\Fixtures;

/**
 * Stands in for the database driver where code only asks for the null date.
 */
class NullDateDatabase
{
    /**
     * @var string
     */
    protected string $nullDate;

    public function __construct(string $nullDate = '0000-00-00 00:00:00')
    {
        $this->nullDate = $nullDate;
    }

    /**
     * @return string
     */
    public function getNullDate(): string
    {
        return $this->nullDate;
    }
}
