<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Factory;

abstract class AbstractConfigurationTree implements \ArrayAccess
{
    abstract public function getSubTree($root);
}
