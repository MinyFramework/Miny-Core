<?php

/**
 * This file is part of the Miny framework.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version accepted by the author in accordance with section
 * 14 of the GNU General Public License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   Miny/HTTP
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\Formatter;

use \Miny\Cache\iCacheDriver;

class FormatterBase
{
    private $formatters = array();
    private $cache;

    public function __construct(iCacheDriver $driver = NULL)
    {
        $this->cache = $driver;
    }

    public function addFormatter(iFormatter $formatter)
    {
        $this->formatters[] = $formatter;
    }

    private function doFormat($text)
    {
        foreach ($this->formatters as $formatter) {
            $text = $formatter->format($text);
        }
        return $text;
    }

    public function format($text)
    {
        if (!is_null($this->cache)) {
            $key = md5($text);
            if (!$this->cache->has($key)) {
                $text = $this->doFormat($text);
                $this->cache->store($key, $text, 3600);
            } else {
                $text = $this->cache->get($key);
            }
        } else {
            $text = $this->doFormat($text);
        }
        return $text;
    }

}