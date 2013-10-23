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
    const DEBUG = 'debug';
    const INFO = 'info';
    const WARNING = 'warning';
    const ERROR = 'error';
    const EXCEPTION = 'exception';

    /**
     * @var string
     */
    private $path;

    /**
     * @var array
     */
    private $messages = array();

    /**
     * @var bool
     */
    private $can_log = true;

    /**
     * @var bool
     */
    private $debug_mode = false;

    /**
     * @param string $path
     * @throws Exception
     */
    public function __construct($path)
    {
        $path = realpath($path);
        try {
            $this->checkPath($path);
        } catch (Exception $e) {
            $this->can_log = false;
            return;
        }
        $this->path = $path;
        if (defined('START_TIME')) {
            $log = $this;
            register_shutdown_function(function()use($log) {
                $message = sprintf('Execution time: %lf s', microtime(true) - START_TIME);
                $log->write($message, 'info');
            });
        }
        register_shutdown_function(array($this, 'saveLog'));
    }

    /**
     * @param string $path
     * @throws InvalidArgumentException
     */
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
     * @param bool $debug
     */
    public function setDebugMode($debug = true)
    {
        $this->debug_mode = $debug;
    }

    /**
     * @return string
     */
    public function getLogFileName()
    {
        return sprintf('%s/log_%s.log', $this->path, date('Y_m_d'));
    }

    /**
     * @param string $message
     * @param string $level
     */
    public function write($message, $level = self::INFO)
    {
        if (!$this->can_log) {
            return;
        }
        if ($level == self::DEBUG && !$this->debug_mode) {
            return;
        }
        $key = time();
        if (!isset($this->messages[$key])) {
            $this->messages[$key] = array();
        }
        $this->messages[$key][] = array($level, $message);
    }

    /**
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
