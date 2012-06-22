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
 * @package   Miny/Translation/Loaders
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny\Translation\Loaders;

use Miny\Translation\Translation;

class PHP extends \Miny\Translation\Loader {

    private $strings_dir;

    public function __construct($dir, $lang, Translation $t) {
        $this->strings_dir = $dir;
        parent::__construct($lang, $t);
    }

    protected function load($lang) {
        $file = $this->strings_dir . '/' . $lang . '.php';
        if (!file_exists($file)) {
            throw new \OutOfBoundsException('Language data not found: ' . $lang);
        }
        return include $file;
    }

}