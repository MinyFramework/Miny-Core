<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
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
    private $runners = [];

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
        $response    = $this->container->get('\\Miny\\HTTP\\Response', [], true);
        $oldResponse = $this->container->setInstance($response);

        $response = $this->doRun($request, $response);

        if ($oldResponse) {
            return $this->container->setInstance($oldResponse);
        }

        return $response;
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @return Response
     * @throws Exceptions\InvalidControllerException
     */
    private function doRun(Request $request, Response $response)
    {
        foreach ($this->runners as $runner) {
            if ($runner->canRun($request)) {
                return $runner->run($request, $response);
            }
        }
        $path = $request->getPath();
        throw new InvalidControllerException("Invalid controller set for path {$path}");
    }
}
