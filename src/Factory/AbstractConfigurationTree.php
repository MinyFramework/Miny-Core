<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Factory;

abstract class AbstractConfigurationTree implements \ArrayAccess
{
    abstract public function getSubTree($root);
}
