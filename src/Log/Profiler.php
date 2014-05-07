<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Log;

use BadMethodCallException;

class Profiler
{
    private static $pattern = 'Profiling %s: Run #: %d, Time: %s ms, Memory: %s';
    private static $units = array(' B', ' kiB', ' MiB', ' GiB', ' TiB', ' PiB');

    /**
     * @var AbstractLog
     */
    private $log;
    private $category;
    private $name;
    private $isRunning;
    private $time;
    private $memory;
    private $runs = 0;

    public function __construct(AbstractLog $log, $category, $name)
    {
        $this->log      = $log;
        $this->category = $category;
        $this->name     = $name;
    }

    public function start()
    {
        $this->time      = microtime(true);
        $this->memory    = memory_get_usage(true);
        $this->isRunning = true;
        $this->runs++;
    }

    public function stop()
    {
        if (!$this->isRunning) {
            throw new BadMethodCallException('Profiler is not started.');
        }
        $this->log->write(Log::PROFILE, $this->category, $this->getMessage());
        $this->isRunning = false;
    }

    private function getMessage()
    {
        $time   = number_format((microtime(true) - $this->time) * 1000, 3);
        $memory = $this->normalizeMemory(memory_get_usage(true) - $this->memory);

        return sprintf(self::$pattern, $this->name, $this->runs, $time, $memory);
    }

    private function normalizeMemory($memory)
    {
        if ($memory === 0) {
            $value = 0;
            $power = 0;
        } else {
            $power = (int) floor(log($memory, 1024));
            $value = round($memory / pow(1024, $power), 2);
        }

        return $value . self::$units[$power];
    }
}
