<?php

/**
 * This file is part of the Prominence framework.
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
 * @package   Prominence/$package_name$
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   $package_version$
 */

namespace Miny\Formatter;

/**
 * $classname$
 *
 * @author Dániel Buga
 */
class CodeColorer implements iFormatter {

    private function highlightSyntax($matches) {
        $text = $matches[1];
        //print_r(preg_split('/\s+/mu', $text));
        return $text;
    }

    public function format($text) {
        return preg_replace_callback('/(?<=\A|\n\n)[~]{3,}(?:.*)[~]*\n*(?s:(.*?))\n*[~]{3,}(?=\Z|\n)/mu', array($this, 'highlightSyntax'), $text);
    }

}