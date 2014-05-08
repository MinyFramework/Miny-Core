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
    /**
     * @var AbstractHeaderSender
     */
    private $sender;

    /**
     * @var array
     */
    private $cookies = array();

    public function __construct(AbstractHeaderSender $sender = null)
    {
        $this->sender = $sender ? : new NativeHeaderSender;
    }

    public function setCookie($name, $value)
    {
        $this->cookies[$name] = $value;
    }

    public function removeCookie($name)
    {
        unset($this->cookies[$name]);
    }

    public function getCookies()
    {
        return $this->cookies;
    }

    public function send()
    {
        foreach ($this as $header => $value) {
            $this->sender->send("{$header}: {$value}");
        }
        foreach ($this->getRawHeaders() as $header) {
            $this->sender->send($header);
        }
        foreach ($this->cookies as $name => $value) {
            $this->sender->sendCookie($name, $value);
        }
    }

    public function serialize()
    {
        $array = array(
            'parent'  => parent::serialize(),
            'cookies' => $this->cookies,
            'sender'  => $this->sender
        );

        return serialize($array);
    }

    public function unserialize($serialized)
    {
        $array         = unserialize($serialized);
        $this->cookies = $array['cookies'];
        $this->sender  = $array['sender'];
        parent::unserialize($array['parent']);
    }
}
