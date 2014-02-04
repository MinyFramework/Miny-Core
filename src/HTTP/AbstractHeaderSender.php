<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\HTTP;

abstract class AbstractHeaderSender
{

    /**
     * @param $header
     */
    abstract public function send($header);

    /**
     * @param $name
     * @param $value
     */
    abstract public function sendCookie($name, $value);
}
