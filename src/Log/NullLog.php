<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Log;

class NullLog extends AbstractLog
{
    public function write($level, $category, $message)
    {

    }
}
