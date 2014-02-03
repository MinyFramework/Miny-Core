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

abstract class BaseController extends Extendable
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @param Application $app The current application instance.
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->init();
    }

    /**
     * Shortcut to fetch a configuration value.
     *
     * @return mixed
     */
    public function getConfig()
    {
        $parameterContainer = $this->app->getParameterContainer();
        return $parameterContainer->offsetGet(func_get_args());
    }

    protected function init()
    {

    }
}
