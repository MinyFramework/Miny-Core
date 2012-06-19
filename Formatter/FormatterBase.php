<?php

namespace Miny\Formatter;

class FormatterBase {

    private $formatters = array();
    private $cache;

    public function setCacheDriver(\Miny\Cache\iCacheDriver $driver) {
        $this->cache = $driver;
    }

    public function addFormatter(iFormatter $formatter) {
        $this->formatters[] = $formatter;
    }

    private function doFormat($text) {
        foreach ($this->formatters as $formatter) {
            $text = $formatter->format($text);
        }
        return $text;
    }

    public function format($text) {
        if (!is_null($this->cache)) {
            $key = md5($text);
            if (!$this->cache->exists($key)) {
                $text = $this->doFormat($text);
                $this->cache->store($key, $text, 3600);
            } else {
                $text = $this->cache->get($key);
            }
        } else {
            $text = $this->doFormat($text);
        }
        return $text;
    }

}