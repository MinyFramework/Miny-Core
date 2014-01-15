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
     * @param BaseApplication $app
     */
    public function __construct(BaseApplication $app)
    {
        $this->app = $app;
        $this->init();
    }

    public function getConfig()
    {
        $parameters = $this->app->getFactory()->getParameters();
        return $parameters[implode(':', func_get_args())];
    }

    protected function init()
    {

    }
}
