<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application\Handlers;

use Miny\Application\Events\UncaughtExceptionEvent;
use Miny\Event\EventDispatcher;
use Miny\Log\Log;

class ErrorHandlers
{
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

    public function handleExceptions($e)
    {
        if (PHP_MAJOR_VERSION >= 7) {
            if (!$e instanceof \Throwable) {
                throw new \InvalidArgumentException("handleExceptions was called with an argument of invalid type");
            }
        } else {
            if (!$e instanceof \Exception) {
                throw new \InvalidArgumentException("handleExceptions was called with an argument of invalid type");
            }
        }

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

    /**
     * @see http://php.net/manual/en/function.set-error-handler.php#112881
     */
    public function handleErrors($errType, $errMsg, $errFile, $errLine)
    {
        if (error_reporting() & $errType === 0) {
            // This error code is not included in error_reporting
            return;
        }
        switch ($errType) {
            default:
            case E_ERROR:
                throw new \ErrorException($errMsg, 0, $errType, $errFile, $errLine);
            case E_WARNING:
                throw new WarningException($errMsg, 0, $errType, $errFile, $errLine);
            case E_PARSE:
                throw new ParseException($errMsg, 0, $errType, $errFile, $errLine);
            case E_NOTICE:
                throw new NoticeException($errMsg, 0, $errType, $errFile, $errLine);
            case E_CORE_ERROR:
                throw new CoreErrorException($errMsg, 0, $errType, $errFile, $errLine);
            case E_CORE_WARNING:
                throw new CoreWarningException($errMsg, 0, $errType, $errFile, $errLine);
            case E_COMPILE_ERROR:
                throw new CompileErrorException($errMsg, 0, $errType, $errFile, $errLine);
            case E_COMPILE_WARNING:
                throw new CoreWarningException($errMsg, 0, $errType, $errFile, $errLine);
            case E_USER_ERROR:
                throw new UserErrorException($errMsg, 0, $errType, $errFile, $errLine);
            case E_USER_WARNING:
                throw new UserWarningException($errMsg, 0, $errType, $errFile, $errLine);
            case E_USER_NOTICE:
                throw new UserNoticeException($errMsg, 0, $errType, $errFile, $errLine);
            case E_STRICT:
                throw new StrictException($errMsg, 0, $errType, $errFile, $errLine);
            case E_RECOVERABLE_ERROR:
                throw new RecoverableErrorException($errMsg, 0, $errType, $errFile, $errLine);
            case E_DEPRECATED:
                throw new DeprecatedException($errMsg, 0, $errType, $errFile, $errLine);
            case E_USER_DEPRECATED:
                throw new UserDeprecatedException($errMsg, 0, $errType, $errFile, $errLine);
        }
    }
}

class WarningException extends \ErrorException
{
}

class ParseException extends \ErrorException
{
}

class NoticeException extends \ErrorException
{
}

class CoreErrorException extends \ErrorException
{
}

class CoreWarningException extends \ErrorException
{
}

class CompileErrorException extends \ErrorException
{
}

class CompileWarningException extends \ErrorException
{
}

class UserErrorException extends \ErrorException
{
}

class UserWarningException extends \ErrorException
{
}

class UserNoticeException extends \ErrorException
{
}

class StrictException extends \ErrorException
{
}

class RecoverableErrorException extends \ErrorException
{
}

class DeprecatedException extends \ErrorException
{
}

class UserDeprecatedException extends \ErrorException
{
}
