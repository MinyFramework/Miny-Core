<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\HTTP;

class NativeHeaderSender extends AbstractHeaderSender
{

    public function send($header)
    {
        header($header);
    }
}
