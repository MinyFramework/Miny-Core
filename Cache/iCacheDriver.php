<?php

namespace Miny\Cache;

interface iCacheDriver {

    public function exists($key);

    public function get($key);

    public function store($key, $data, $ttl);

    public function remove($key);

    public function close();
}