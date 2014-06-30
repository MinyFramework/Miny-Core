<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application\Events;

use Exception;
use Miny\CoreEvents;
use Miny\Event\Event;

class UncaughtExceptionEvent extends Event
{
    /**
     * @var \Exception
     */
    private $exception;

    public function __construct(Exception $exception)
    {
        parent::__construct(CoreEvents::UNCAUGHT_EXCEPTION);
        $this->exception = $exception;
    }

    /**
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }
}
