<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Test;

class Client
{
    private $history;
    private $current;

    public function request($method, $url)
    {
        $this->last_request = array(
            'method' => $method,
            'url'    => $url
        );
    }

    public function back()
    {
        if ($this->current < 1) {

        }
        $this->request('GET', $this->history[--$this->current]);
    }

    public function forward()
    {
        if ($this->current == count($this->history) - 1) {

        }
        $this->request('GET', $this->history[++$this->current]);
    }

    public function reload()
    {
        $method = $this->last_request['method'];
        $url    = $this->last_request['url'];
        $this->request($method, $url);
    }
}
