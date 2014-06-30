<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Log;

class LogMessage
{
    private $time;
    private $message;
    private $category;
    private $level;

    public function __construct($level, $time, $category, $message)
    {
        $this->level    = $level;
        $this->time     = $time;
        $this->category = $category;
        $this->message  = $message;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @return mixed
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return mixed
     */
    public function getTime()
    {
        return $this->time;
    }

}
