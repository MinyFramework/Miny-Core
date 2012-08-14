<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application;

use Miny\Application\Exceptions\BadModuleException;

abstract class Module
{
    protected $application;

    public function __construct()
    {
        foreach ($this->includes() as $file) {
            if (!is_file($file)) {
                throw new BadModuleException('Required file not found: ' . $file);
            }
            include_once $file;
        }
    }

    public function getDependencies()
    {
        return array();
    }

    public function includes()
    {
        return array();
    }

    public abstract function init(Application $app);
}