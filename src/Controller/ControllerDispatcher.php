<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Controller;

use Miny\Controller\Exceptions\InvalidControllerException;
use Miny\Factory\Container;
use Miny\HTTP\Request;
use Miny\HTTP\Response;

class ControllerDispatcher
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var AbstractControllerRunner[]
     */
    private $runners = array();

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function addRunner(AbstractControllerRunner $runner)
    {
        $this->runners[] = $runner;
    }

    /**
     * @param Request $request
     *
     * @throws InvalidControllerException
     *
     * @return Response
     */
    public function runController(Request $request)
    {
        /** @var $response Response */
        $response    = $this->container->get('\\Miny\\HTTP\\Response', array(), true);
        $oldResponse = $this->container->setInstance($response);

        $controller     = $request->get()->get('controller');
        $runnerExecuted = false;
        foreach ($this->runners as $runner) {
            if ($runner->canRun($controller)) {
                $response       = $runner->run($controller, $request, $response);
                $runnerExecuted = true;
                break;
            }
        }
        if (!$runnerExecuted) {
            $path = $request->getPath();
            throw new InvalidControllerException("Invalid controller set for path {$path}");
        }

        if ($oldResponse) {
            return $this->container->setInstance($oldResponse);
        }

        return $response;
    }
}
