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
     * @var Application
     */
    protected $app;

    /**
     * @param Application $app
     */
    public function __construct(BaseApplication $app)
    {
        $this->app = $app;
        $this->init();
    }

    protected function init()
    {

    }

    /**
     * @param string $name
     * @return Object
     */
    public function service($name)
    {
        return $this->app->$name;
    }

}