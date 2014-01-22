<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\HTTP;

class ResponseHeaders extends Headers
{
    private $sender;

    public function __construct(AbstractHeaderSender $sender = null)
    {
        $this->sender = $sender ?: new NativeHeaderSender;
    }

    public function send()
    {
        foreach ($this as $header => $value) {
            $this->sender->send($header . ': ' . $value);
        }
        foreach ($this->getRawHeaders() as $header) {
            $this->sender->send($header);
        }
    }
}
