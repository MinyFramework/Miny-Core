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
 * @package   Miny
 * @copyright 2012 Dániel Buga <daniel@bugadani.hu>
 * @license   http://www.gnu.org/licenses/gpl.txt
 *            GNU General Public License
 * @version   1.0
 */

namespace Miny;

class Log
{
    private $path;

    public function __construct($path)
    {
        $path = realpath($path);
        if (!is_dir($path)) {
            if (!mkdir($path, 0777, true)) {
                throw new \InvalidArgumentException('Path not exists: ' . $path);
            }
        }
        if (!is_writable($path)) {
            throw new \InvalidArgumentException('Path is not writable: ' . $path);
        }
        $this->path = $path;
    }

    public function getLogFileName()
    {
        return $this->path . '/log_' . date('Y_m_d') . '.log';
    }

    public function write($message, $level = 'info')
    {
        $log = sprintf("[%s] %s: %s\n", date('Y-m-d H:i:s'), $level, $message);
        $file = $this->getLogFileName();
        if (file_put_contents($file, $log, FILE_APPEND) === false) {
            throw new \RuntimeException('Could not write log file: ' . $file);
        }
    }

}