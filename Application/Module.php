<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Application;

abstract class Module
{
    public function getDependencies()
    {
        return array();
    }

    public function getVersion()
    {
        return '1.0';
    }

    public abstract function init(Application $app);
}