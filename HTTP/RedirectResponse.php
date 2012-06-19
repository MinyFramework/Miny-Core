<?php

namespace Miny\HTTP;

class RedirectResponse extends Response {

    public function __construct($url, $code = 301) {
        parent::__construct('', $code);
        $this->setHeader('Location', $url);
    }

    public function setContent($content) {

    }

    public function send() {
        $this->sendHeaders();
    }

}