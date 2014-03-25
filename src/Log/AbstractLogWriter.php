<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Log;

abstract class AbstractLogWriter
{
    /**
     * @var Log
     */
    private $log;

    abstract public function add(LogMessage $message);

    abstract public function commit();

    abstract public function reset();

    public function attach(Log $log)
    {
        $this->log = $log;
    }

    public function getLevelName($level)
    {
        return Log::getLevelName($level);
    }

    public function __destruct()
    {
        $this->detach();
    }

    public function detach()
    {
        if (isset($this->log)) {
            $this->log->removeWriter($this);
        }
    }
}
