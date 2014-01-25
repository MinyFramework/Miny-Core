<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Controller;

use Miny\Application\BaseApplication;
use Miny\Extendable;

abstract class BaseController extends Extendable
{
    /**
     * @var BaseApplication
     */
    protected $app;

    /**
     * @param BaseApplication $app The current application instance.
     */
    public function __construct(BaseApplication $app)
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
        $factory = $this->app->getFactory();
        return $factory[func_get_args()];
    }

    protected function init()
    {

    }
}
