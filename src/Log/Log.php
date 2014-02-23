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
     * @var Profiler[]
     */
    private $profilers;
    private $messages;
    private $messageNum;
    private $flushLimit;

    public function __construct()
    {
        $this->writers    = array();
        $this->profilers  = array();
        $this->flushLimit = 100;
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
        $this->messages   = array();
        $this->messageNum = 0;
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
        $message = $this->formatMessage($message, $args);
        $this->messageNum++;
        $this->messages[] = new LogMessage($level, microtime(true), $category, $message);

        if ($this->messageNum === $this->flushLimit) {
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
        if (empty($this->messages) || empty($this->writers)) {
            return;
        }
        foreach ($this->messages as $message) {
            /** @var $message LogMessage */
            $level = $message->getLevel();
            foreach ($this->writers[$level] as $writer) {
                /** @var $writer AbstractLogWriter */
                $writer->add($message);
            }
        }

        foreach ($this->writers as $writers) {
            foreach ($writers as $writer) {
                /** @var $writer AbstractLogWriter */
                $writer->commit();
            }
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
        if (!isset($this->writers[$level])) {
            $this->writers[$level] = array();
        }
        $this->writers[$level][] = $writer;
    }

    public function removeWriter(AbstractLogWriter $writer)
    {
        foreach ($this->writers as $level => &$writers) {
            $key = array_search($writer, $writers);
            unset($writers[$key]);
            if (empty($writers)) {
                unset($this->writers[$level]);
            }
        }
    }
}
