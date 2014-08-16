<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Log;

class Log extends AbstractLog
{
    const PROFILE = 1;
    const DEBUG   = 2;
    const INFO    = 3;
    const WARNING = 4;
    const ERROR   = 5;

    private static $names = [
        self::PROFILE => 'Profile',
        self::DEBUG   => 'Debug',
        self::INFO    => 'Info',
        self::WARNING => 'Warning',
        self::ERROR   => 'Error'
    ];

    /**
     * @var AbstractLogWriter
     */
    private $writers = [
        Log::PROFILE => [],
        Log::DEBUG   => [],
        Log::INFO    => [],
        Log::WARNING => [],
        Log::ERROR   => []
    ];

    /**
     * @var AbstractLogWriter[]
     */
    private $allWriters = [];

    /**
     * @var Profiler[]
     */
    private $profilers = [];

    private $messageNum = 0;
    private $flushLimit = 100;

    /**
     * @var LogMessage[]
     */
    private $messageBuffer = [];

    public static function getLevelName($level)
    {
        if (isset(self::$names[$level])) {
            return self::$names[$level];
        }

        return "Unknown ({$level})";
    }

    public function setFlushLimit($limit)
    {
        $this->flushLimit = (int) $limit;
        if ($this->messageNum >= $this->flushLimit) {
            $this->flush();
        }
    }

    private function reset()
    {
        $this->messageNum    = 0;
        $this->messageBuffer = [];
        foreach ($this->allWriters as $writer) {
            $writer->reset();
        }
    }

    public function startProfiling($category, $name)
    {
        $profiler = $this->createProfiler($category, $name);
        $profiler->start();

        return $profiler;
    }

    /**
     * @param string $category
     * @param string $name
     *
     * @return Profiler
     */
    private function createProfiler($category, $name)
    {
        $key = $category . '.' . $name;
        if (!isset($this->profilers[$key])) {
            $this->profilers[$key] = new Profiler($this, $category, $name);
        }

        return $this->profilers[$key];
    }

    public function write($level, $category, $message)
    {
        if (func_num_args() > 3) {
            $args = array_slice(func_get_args(), 3);
            if (is_array($args[0])) {
                $args = $args[0];
            }
            $message = vsprintf($message, $args);
        }

        $this->messageBuffer[] = new LogMessage(
            $level,
            microtime(true),
            $category,
            $message
        );

        if (++$this->messageNum === $this->flushLimit) {
            $this->flush();
        }
    }

    public function flush()
    {
        foreach ($this->messageBuffer as $message) {
            $level = $message->getLevel();
            foreach ($this->writers[$level] as $writer) {
                /** @var $writer AbstractLogWriter */
                $writer->add($message);
            }
        }

        foreach ($this->allWriters as $writer) {
            $writer->commit();
        }
        $this->reset();
    }

    public function registerWriter(AbstractLogWriter $writer, $levels = null)
    {
        $writer->attach($this);
        $this->allWriters[] = $writer;

        if ($levels === null) {
            $levels  = [
                self::PROFILE,
                self::DEBUG,
                self::INFO,
                self::WARNING,
                self::ERROR
            ];
            $isArray = true;
        } else {
            $isArray = is_array($levels);
        }

        if ($isArray) {
            foreach ($levels as $level) {
                $this->addWriterWithLevel($writer, $level);
            }
        } else {
            $this->addWriterWithLevel($writer, $levels);
        }
    }

    private function addWriterWithLevel(AbstractLogWriter $writer, $level)
    {
        $this->writers[$level][] = $writer;
    }

    public function removeWriter(AbstractLogWriter $writer)
    {
        $filter = function ($item) use ($writer) {
            return $item !== $writer;
        };
        foreach ($this->writers as $level => $writers) {
            $this->writers[$level] = array_filter($writers, $filter);
        }
        $this->allWriters = array_filter($this->allWriters, $filter);
    }
}
