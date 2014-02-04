<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Controller;

use Miny\Application\Application;
use Miny\Extendable;
use Miny\Factory\ParameterContainer;

abstract class BaseController extends Extendable
{
    /**
     * @var Application
     */
    protected $container;

    /**
     * @param ParameterContainer $container The current application configuration.
     */
    public function __construct(ParameterContainer $container)
    {
        $this->container = $container;
        $this->init();
    }

    /**
     * Shortcut to fetch a configuration value.
     *
     * @return mixed
     */
    public function getConfig()
    {
        return $this->container->offsetGet(func_get_args());
    }

    protected function init()
    {

    }
}
