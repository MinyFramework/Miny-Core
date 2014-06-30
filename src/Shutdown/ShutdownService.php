<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Shutdown;

/**
 * ShutdownService provides an easy to use interface to register shutdown functions ordered by priorities.
 */
class ShutdownService
{
    private $callbacks = array();
    private $lowestPriority = -1;

    public function __construct()
    {
        register_shutdown_function(array($this, 'callShutdownFunctions'));
    }

    /**
     * Registers a callback to be called on shutdown.
     *
     * @param callable $callback
     * @param null|int $priority The priority of the callback. Lowest number means higher priority.
     *
     * @throws \InvalidArgumentException
     */
    public function register($callback, $priority = null)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('$callback needs to be callable.');
        }
        $priority = $this->getPriority($priority);
        if (!isset($this->callbacks[$priority])) {
            $this->callbacks[$priority] = array($callback);
        } else {
            $this->callbacks[$priority][] = $callback;
        }
        $this->lowestPriority = max($priority, $this->lowestPriority);
    }

    /**
     * @param $priority
     *
     * @return int
     */
    private function getPriority($priority)
    {
        if ($priority === null || !is_int($priority)) {
            return $this->lowestPriority + 1;
        }

        return $priority;
    }

    public function callShutdownFunctions()
    {
        ksort($this->callbacks);
        foreach ($this->callbacks as $callbacks) {
            foreach ($callbacks as $callback) {
                $callback();
            }
        }
    }
}
