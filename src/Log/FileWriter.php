<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
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

    /**
     * @var string
     */
    private $buffer = '';

    public function __construct($path)
    {
        $this->path = $path;
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }

    public function add(LogMessage $message)
    {
        $date      = date('Y-m-d H:i:s', $message->getTime());
        $levelName = $this->getLevelName($message->getLevel());
        $category  = $message->getCategory();
        $text      = $message->getMessage();
        $this->buffer .= "[{$date}] {$levelName}: {$category} - {$text}\n";
    }

    public function commit()
    {
        $file = '/log_' . date('Y_m_d') . '.txt';
        file_put_contents($this->path . $file, $this->buffer, FILE_APPEND);
    }

    public function reset()
    {
        $this->buffer = '';
    }
}
