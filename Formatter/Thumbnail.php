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
 * @package   Miny/Formatter
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\Formatter;

class Thumbnail implements iFormatter
{
    private $pattern = '<a href="%1$s$2" class="thumbnail">![$1](%1$s%2$s$2)<span>$1</span></a>';
    private $thumbnail_script;
    private $dir;

    public function __construct($dir, $thumbnail_script, $pattern = NULL)
    {
        $this->thumbnail_script = $thumbnail_script;
        $this->dir = $dir;
        if (!is_null($pattern)) {
            $this->pattern = $pattern;
        }
    }

    public function format($text)
    {
        $replace = sprintf($this->pattern, $this->dir, $this->thumbnail_script);
        return preg_replace('/(?<!\\\)!\[thumbnail:(.+?)\]\((.+?)\)/u', $replace, $text);
    }

}