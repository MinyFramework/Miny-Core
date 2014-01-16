<?php

namespace Miny\Application;

use Closure;
use InvalidArgumentException;
use Miny\Controller\WorkerController;

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
            } elseif (is_array($runnable) && count($runnable) == 2) {
                list($class, $method) = $runnable;
            } else {
                throw new InvalidArgumentException('Invalid runnable set.');
            }
            $runnable = array($class, $method);
        }
        $this->runnable      = $runnable;
        $this->one_time      = $one_time;
        $this->run_condition = $run_condition;
        $this->workload      = $workload;
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
            $function = $this->run_condition;
            return $function();
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
                $factory = $app->getFactory();
                if ($factory->has($class . '_controller')) {
                    $class = $factory->get($class . '_controller');
                } else {
                    if (!class_exists($class)) {
                        $class = '\Application\Controllers\\' . ucfirst($class) . 'Controller';
                        if (!class_exists($class)) {
                            throw new \UnexpectedValueException('Class not found: ' . $class);
                        }
                    }
                    $class = new $class($app);
                }
                //Cache our runnable to avoid reinstantiation
                $this->runnable[0] = $class;
            }

            if ($class instanceof WorkerController) {
                $class->$method($this);
                return;
            }
        }
        $runnable = $this->runnable;
        $runnable($app, $this);
    }
}
