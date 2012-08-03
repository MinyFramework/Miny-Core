<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny;

use Exception;
use InvalidArgumentException;
use RuntimeException;

class Log
{
    private $path;
    private $messages = array();
    private $can_log = true;

    public function __construct($path, $force_log = false)
    {
        $path = realpath($path);
        try {
            $this->checkPath($path);
        } catch (Exception $e) {
            if ($force_log) {
                throw $e;
            } else {
                $this->can_log = false;
            }
        }
        $this->path = $path;
    }

    private function checkPath($path)
    {
        if (!is_dir($path)) {
            if (!mkdir($path, 0777, true)) {
                throw new InvalidArgumentException('Path not exists: ' . $path);
            }
        }
        if (!is_writable($path)) {
            throw new InvalidArgumentException('Path is not writable: ' . $path);
        }
    }

    public function getLogFileName()
    {
        return $this->path . '/log_' . date('Y_m_d') . '.log';
    }

    public function write($message, $level = 'info')
    {
        if (!$this->can_log) {
            return;
        }
        $this->messages[] = sprintf("[%s] %s: %s\n", date('Y-m-d H:i:s'), $level, $message);
    }

    public function __destruct()
    {
        if (empty($this->messages)) {
            return;
        }
        $file = $this->getLogFileName();
        $data = implode('', $this->messages);
        $data .= "\n";
        if (file_put_contents($file, $data, FILE_APPEND) === false) {
            throw new RuntimeException('Could not write log file: ' . $file);
        }
    }

}