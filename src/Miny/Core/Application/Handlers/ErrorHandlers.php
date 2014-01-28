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
use Miny\Log;

class ErrorHandlers
{
    private static $loggable = array(
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
    protected $log;

    public function __construct(Log $log)
    {
        $this->log = $log;
        set_error_handler(array($this, 'logError'));
    }

    public function logError($errno, $errstr, $errfile, $errline)
    {
        if (isset(self::$loggable[$errno])) {
            $message = sprintf('%s in %s on line %s', $errstr, $errfile, $errline);
            $this->log->write($message, self::$loggable[$errno]);
        } else {
            throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
        }
    }

    public function logException(Exception $e)
    {
        $class = get_class($e);
        $this->log->$class("%s \n Trace: %s", $e->getMessage(), $e->getTraceAsString());
    }
}
