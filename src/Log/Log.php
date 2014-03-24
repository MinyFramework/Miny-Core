<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Log;

class Log
{
    const PROFILE = 0;
    const DEBUG   = 1;
    const INFO    = 2;
    const WARNING = 3;
    const ERROR   = 4;

    /**
     * @var AbstractLogWriter
     */
    private $writers;

    /**
     * @var AbstractLogWriter[]
     */
    private $allWriters;

    /**
     * @var Profiler[]
     */
    private $profilers;

    private $messageNum;
    private $flushLimit;

    /**
     * @var LogMessage[]
     */
    private $messageBuffer;

    public function __construct()
    {
        $this->writers       = array(
            Log::PROFILE => array(),
            Log::DEBUG   => array(),
            Log::INFO    => array(),
            Log::WARNING => array(),
            Log::ERROR   => array()
        );
        $this->allWriters    = array();
        $this->messageBuffer = array();
        $this->profilers     = array();
        $this->flushLimit    = 100;
        $this->reset();
    }

    public function setFlushLimit($limit)
    {
        $this->flushLimit = (int)$limit;
        if ($this->messageNum >= $this->flushLimit) {
            $this->flush();
        }
    }

    private function reset()
    {
        $this->messageNum = 0;
        $this->messageBuffer = array();
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
        $args = array_slice(func_get_args(), 3);
        if (isset($args[0]) && is_array($args[0])) {
            $args = $args[0];
        }

        $this->messageBuffer[] = new LogMessage(
            $level,
            microtime(true),
            $category,
            $this->formatMessage($message, $args)
        );

        if (++$this->messageNum === $this->flushLimit) {
            $this->flush();
        }
    }

    /**
     * @param string $message
     * @param array  $args
     *
     * @return string
     */
    private function formatMessage($message, array $args)
    {
        if (!empty($args)) {
            $message = vsprintf($message, $args);
        }

        return $message;
    }

    public function getLevelName($level)
    {
        static $names = array(
            self::PROFILE => 'Profile',
            self::DEBUG   => 'Debug',
            self::INFO    => 'Info',
            self::WARNING => 'Warning',
            self::ERROR   => 'Error'
        );
        if (isset($names[$level])) {
            return $names[$level];
        }

        return 'Unknown (' . $level . ')';
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
        if ($levels === null) {
            $levels = array(
                self::PROFILE,
                self::DEBUG,
                self::INFO,
                self::WARNING,
                self::ERROR
            );
        }
        $this->allWriters[] = $writer;
        if (is_array($levels)) {
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
        foreach ($this->writers as $level => $writers) {
            $key = array_search($writer, $writers);
            unset($this->writers[$level][$key]);
        }
    }
}
