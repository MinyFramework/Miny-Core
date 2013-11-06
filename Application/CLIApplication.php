<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application;

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

    /**
     * @param string $name
     * @param mixed $runnable
     * @param mixed $condition
     * @param bool $one_time
     * @return Job
     * @throws InvalidArgumentException
     */
    public function addJob($name, $runnable, $condition = null, $one_time = false)
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException('Job name must be a string.');
        }
        if (!$runnable instanceof Job) {
            $runnable = new Job($runnable, $one_time, $condition);
        }

        $this->log->info('Registering new %s "%s"', ($one_time ? 'one-time job' : 'job'), $name);
        $this->jobs[$name] = $runnable;
        return $runnable;
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
        while (!$this->exit_requested && !empty($this->jobs)) {

            foreach ($this->jobs as $name => $job) {
                if ($job->canRun()) {
                    $job->run($this, $this->argc, $this->argv);
                    if ($job->isOneTimeJob()) {
                        $this->log->info('Removing one-time job %s', $name);
                        $this->removeJob($name);
                    }
                    $this->log->saveLog();
                } else {
                    $this->log->info('Skipping job %s', $name);
                }
            }
        }
    }

}
