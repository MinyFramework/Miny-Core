<?php

namespace Miny\Application;

use Closure;
use InvalidArgumentException;
use Miny\Controller\CLIController;
use UnexpectedValueException;

class Job
{
    private $runnable;
    private $one_time;
    private $run_condition;

    public function __construct($runnable, $run_condition = null, $one_time = false)
    {
        if (is_string($runnable)) {
            if (!class_exists($runnable)) {
                $class = '\Application\Controllers\\' . ucfirst($runnable) . 'Controller';
                if (!class_exists($class)) {
                    throw new UnexpectedValueException('Class not exists: ' . $class);
                }
                $runnable = $class;
            }
        } else if (!is_callable($runnable) && !$runnable instanceof Closure && !$runnable instanceof CLIController) {
            throw new InvalidArgumentException('Job must be a callable object, a CLIController instance or a CLIController name.');
        }
        $this->runnable = $runnable;
        $this->one_time = $one_time;
        $this->run_condition = $run_condition;
    }

    public function isOneTimeJob()
    {
        return $this->one_time;
    }

    public function setRunCondition($run_condition)
    {
        $this->run_condition = $run_condition;
    }

    public function canRun()
    {
        if (is_callable($this->run_condition)) {
            return call_user_func($this->run_condition);
        }
        return true;
    }

    public function run(CLIApplication $app, $argc, $argv)
    {
        if (is_string($this->runnable)) {
            //Lazily instantiate CLIController
            $this->runnable = new $this->runnable($app);
        }
        if ($this->runnable instanceof CLIController) {
            $this->runnable->run($argc, $argv);
        } else {
            call_user_func($this->runnable, $app, $argc, $argv);
        }
    }

}
