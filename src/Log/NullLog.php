<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
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
