<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application\Handlers;

use ErrorException;
use Exception;
use Miny\Application\Events\UncaughtExceptionEvent;
use Miny\Event\EventDispatcher;
use Miny\Log\Log;

class ErrorHandlers
{
    private static $internalLogCategories = array(
        E_NOTICE       => 'Notice (PHP)',
        E_USER_NOTICE  => 'Notice',
        E_WARNING      => 'Warning (PHP)',
        E_USER_WARNING => 'Warning',
        E_DEPRECATED   => 'Deprecated notice (PHP)',
        E_STRICT       => 'Strict notice (PHP)'
    );

    /**
     * @var Log
     */
    private $log;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    public function __construct(Log $log, EventDispatcher $eventDispatcher)
    {
        $this->log             = $log;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function handleExceptions(Exception $e)
    {
        $this->log->write(
            Log::ERROR,
            get_class($e),
            "%s \n Trace: %s",
            $e->getMessage(),
            $e->getTraceAsString()
        );
        $event = $this->eventDispatcher->raiseEvent(
            new UncaughtExceptionEvent($e)
        );
        if (!$event->isHandled()) {
            // Rethrow the exception that we did not handle.
            throw $e;
        }
    }

    public function handleErrors($errNo, $errStr, $errFile, $errLine)
    {
        if (!isset(self::$internalLogCategories[$errNo])) {
            throw new ErrorException($errStr, $errNo, 0, $errFile, $errLine);
        }
        $this->log->write(
            Log::WARNING,
            self::$internalLogCategories[$errNo],
            '%s in %s on line %s',
            $errStr,
            $errFile,
            $errLine
        );
    }
}
