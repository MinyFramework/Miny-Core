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
    const DEBUG     = 'debug';
    const INFO      = 'info';
    const WARNING   = 'warning';
    const ERROR     = 'error';
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
        try {
            $this->checkPath($path);
        } catch (Exception $e) {
            $this->can_log = false;
            return;
        }
        $this->path = $path;
        $log        = $this;
        register_shutdown_function(function()use($log) {
            if (defined('START_TIME')) {
                $message = sprintf('Execution time: %lf s', microtime(true) - START_TIME);
                $log->write($message, 'info');
            }
            $log->write("End of execution.\n", 'info');
            $log->saveLog();
        });
    }

    /**
     * @param string $path
     * @throws InvalidArgumentException
     */
    private function checkPath($path)
    {
        if (!is_dir($path)) {
            if (!mkdir($path, 0777, true)) {
                throw new InvalidArgumentException('Could not create directory: ' . $path);
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
     * @param string $name
     * @param array $args
     */
    public function __call($name, $args)
    {
        $message = array_shift($args);
        if (is_array(current($args))) {
            $args = current($args);
        }
        $this->write(vsprintf($message, $args), $name);
    }

    private function writeFile($file, $data)
    {
        if (file_put_contents($file, $data, FILE_APPEND) === false) {
            throw new RuntimeException('Could not write log file: ' . $file);
        }
    }

    private function assembleLogMessage()
    {
        $data = '';
        foreach ($this->messages as $time => $messages) {
            $time = date('Y-m-d H:i:s', $time);
            foreach ($messages as $message) {
                $data .= sprintf("[%s] %s: %s\n", $time, $message[0], $message[1]);
            }
        }
        return $data;
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
        $data = $this->assembleLogMessage();

        $this->writeFile($file, $data);
        $this->messages = array();
    }
}
