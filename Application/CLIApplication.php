<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application;

use Closure;
use InvalidArgumentException;
use RuntimeException;

require_once __DIR__ . '/BaseApplication.php';

class CLIApplication extends BaseApplication
{
    private $jobs = array();
    private $argc;
    private $argv;
    private $exit_requested = false;

    public function __construct($directory, $environment = self::ENV_PROD, $include_configs = true)
    {
        global $argc, $argv;

        if (!isset($argc, $argv)) {
            throw new RuntimeException('CLIApplication can only run in CLI environment.');
        }
        ignore_user_abort(true);
        set_time_limit(0);
        parent::__construct($directory, $environment, $include_configs);
        $this->argc = $argc;
        $this->argv = $argv;
        unset($argc, $argv);
    }

    public function addJob($name, $job, $one_time = false)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException('Job name must be a string.');
        }
        if (!is_callable($job) && !$job instanceof Closure) {
            throw new InvalidArgumentException('Job must be a callable object.');
        }
        $this->jobs[$name] = array($job, $one_time);
    }

    public function removeJob($name)
    {
        unset($this->jobs[$name]);
    }

    public function requestExit()
    {
        $this->exit_requested = true;
    }

    public function run()
    {
        date_default_timezone_set($this['default_timezone']);
        while (!$this->exit_requested || empty($this->jobs)) {
            foreach ($this->jobs as $key => $obj) {
                list($job, $one_time) = $obj;
                call_user_func($job, $this, $this->argc, $this->argv);
                if ($one_time) {
                    $this->removeJob($key);
                }
            }
            $this->log->saveLog();
        }
    }

}
