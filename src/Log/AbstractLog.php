<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Log;

abstract class AbstractLog
{
    abstract public function write($level, $category, $message);
}
