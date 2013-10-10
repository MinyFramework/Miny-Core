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

    /**
     *
     * @param string $path
     * @param boolean $force_log
     * @throws Exception
     */
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
        register_shutdown_function(array($this, 'saveLog'));
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

    /**
     *
     * @return string
     */
    public function getLogFileName()
    {
        return $this->path . '/log_' . date('Y_m_d') . '.log';
    }

    /**
     *
     * @param string $message
     * @param string $level
     */
    public function write($message, $level = 'info')
    {
        if (!$this->can_log) {
            return;
        }
        $key = time();
        if (!isset($this->messages[$key])) {
            $this->messages[$key] = array();
        }
        $this->messages[$key][] = array($level, $message);
    }

    /**
     *
     * @throws RuntimeException
     */
    public function saveLog()
    {
        if (empty($this->messages)) {
            return;
        }
        $file = $this->getLogFileName();

        $data = '';

        foreach ($this->messages as $time => $messages) {
            $time = date('Y-m-d H:i:s', $time);
            foreach ($messages as $message) {
                $data .= sprintf("[%s] %s: %s\n", $time, $message[0], $message[1]);
            }
        }

        $data .= "\n";
        if (file_put_contents($file, $data, FILE_APPEND) === false) {
            throw new RuntimeException('Could not write log file: ' . $file);
        }
    }

}

