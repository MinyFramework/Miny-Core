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

    /**
     * @inheritdoc
     */
    public function send($header)
    {
        header($header);
    }

    /**
     * @inheritdoc
     */
    public function sendCookie($name, $value)
    {
        setcookie($name, $value);
    }
}
