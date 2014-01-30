<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Log;

class FileWriter extends AbstractLogWriter
{
    /**
     * @var string
     */
    private $path;
    private $buffer;

    public function __construct($path)
    {
        $this->path   = $path;
        $this->buffer = '';
    }

    public function add(LogMessage $message)
    {
        $this->buffer .= sprintf(
            "[%s] %s: %s - %s\n",
            date('Y-m-d H:i:s', $message->getTime()),
            $message->getCategory(),
            $message->getLevel(),
            $message->getMessage()
        );
    }

    public function commit()
    {
        $file = 'log_' . date('Y_m_d') . '.txt';
        file_put_contents($this->path . $file, $this->buffer, FILE_APPEND);
        $this->buffer = '';
    }
}
