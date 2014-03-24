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
    /**
     * @var Log
     */
    private $log;
    private $category;
    private $name;
    private $isRunning;
    private $time;
    private $memory;
    private $runs;

    public function __construct(Log $log, $category, $name)
    {
        $this->log      = $log;
        $this->category = $category;
        $this->name     = $name;
        $this->runs     = 0;
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
        $pattern = 'Profiling %s: Run #: %d, Time: %s ms, Memory: %s';
        return sprintf($pattern, $this->name, $this->runs, $time, $memory);
    }

    private function normalizeMemory($memory)
    {
        static $units = array(' B', ' kiB', ' MiB', ' GiB', ' TiB', ' PiB');

        $power = (int) floor(log($memory, 1024));
        $value = round($memory / pow(1024, $power), 2);

        return $value . $units[$power];
    }
}
