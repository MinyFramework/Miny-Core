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
use Miny\Utils\ArrayUtils;

abstract class BaseController extends Extendable
{
    /**
     * @var Application
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

    public function getConfig() {
        $parameters = $this->app->getFactory()->getParameters();
        return ArrayUtils::getByPath($parameters, func_get_args());
    }

    protected function init()
    {

    }
}
