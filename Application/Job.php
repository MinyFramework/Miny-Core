<?php

namespace Miny\Application;

use Closure;
use InvalidArgumentException;
use Miny\Controller\WorkerController;
use UnexpectedValueException;

class Job
{
    private $runnable;
    private $one_time;
    private $run_condition;
    private $workload;

    public function __construct($runnable, $workload = null, $run_condition = null, $one_time = false)
    {
        if (!is_callable($runnable) && !$runnable instanceof Closure) {
            if (is_string($runnable)) {
                $class  = $runnable;
                $method = 'run';
            } else if (is_array($runnable) && count($runnable) == 2) {
                list($class, $method) = $runnable;
            } else {
                throw new InvalidArgumentException('Invalid runnable set.');
            }
            $runnable = array($this->getClassName($class), $method);
        }
        $this->runnable      = $runnable;
        $this->one_time      = $one_time;
        $this->run_condition = $run_condition;
        $this->workload      = $workload;
    }

    private function getClassName($name)
    {
        if (class_exists($name)) {
            return $name;
        }
        $class = '\Application\Controllers\\' . ucfirst($name) . 'Controller';
        if (class_exists($class)) {
            return $class;
        }
        throw new UnexpectedValueException('Class not exists: ' . $class);
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

    public function getWorkload()
    {
        return $this->workload;
    }

    public function run(WorkerApplication $app)
    {
        if (is_array($this->runnable)) {
            list($class, $method) = $this->runnable;

            if (is_string($class)) {
                //Lazily instantiate WorkerController
                //Keep it in the same variable for later use
                $class = new $class($app);

                //Cache our runnable to avoid reinstantiation
                $this->runnable = array($class, $method);
            }

            if ($class instanceof WorkerController) {
                $class->$method($this);
                return;
            }
        }
        call_user_func($this->runnable, $app, $this);
    }
}
