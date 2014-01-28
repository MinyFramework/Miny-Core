<?php

namespace Miny\HTTP;

use SessionHandlerInterface;

if (PHP_MINOR_VERSION >= 4) {

    interface iSessionHandler extends SessionHandlerInterface
    {

    }

} else {

    interface iSessionHandler
    {

        public function open();

        public function close();

        public function read($key);

        public function write($key, $value);

        public function destroy($key);

        public function gc($lifetime);
    }

}