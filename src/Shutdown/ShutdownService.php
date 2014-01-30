<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Shutdown;

use InvalidArgumentException;

/**
 * ShutdownService provides an easy to use interface to register shutdown functions ordered by priorities.
 */
class ShutdownService
{
    private $callbacks;
    private $lowest_priority;

    public function __construct()
    {
        $this->callbacks       = array();
        $this->lowest_priority = -1;
        register_shutdown_function(array($this, 'callShutdownFunctions'));
    }

    /**
     * @param callable $callback
     * @param null|int $priority
     *
     * @throws InvalidArgumentException
     */
    public function register($callback, $priority = null)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Callback needs to be callable.');
        }
        $priority = $this->getPriority($priority);
        if (!isset($this->callbacks[$priority])) {
            $this->callbacks[$priority] = array();
        }
        $this->callbacks[$priority][] = $callback;
        $this->setLowestPriority($priority);
    }

    /**
     * @param $priority
     *
     * @return int
     */
    private function getPriority($priority)
    {
        if ($priority === null || !is_int($priority)) {
            return $this->lowest_priority + 1;
        }
        return $priority;
    }

    /**
     * @param $priority
     */
    private function setLowestPriority($priority)
    {
        $this->lowest_priority = max($priority, $this->lowest_priority);
    }

    public function callShutdownFunctions()
    {
        foreach ($this->callbacks as $callbacks) {
            foreach ($callbacks as $callback) {
                $callback();
            }
        }
    }
}
