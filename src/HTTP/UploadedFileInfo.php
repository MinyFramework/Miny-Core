<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\HTTP;

class UploadedFileInfo
{
    const APPEND_LOCAL_NAME = 1;
    const OVERWRITE = 2;

    private $tempName;
    private $fileName;
    private $size;
    private $type;
    private $error;

    public function __construct($tempName, $fileName, $type, $size, $error)
    {
        $this->tempName = $tempName;
        $this->fileName = $fileName;
        $this->type     = $type;
        $this->size     = $size;
        $this->error    = $error;

        if (strpos($fileName, '/') !== false || strpos($fileName, '\\') !== false) {
            throw new \InvalidArgumentException("File name must not contain directory separators.");
        }
    }

    public function getFileName()
    {
        return $this->fileName;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function getTempName()
    {
        return $this->tempName;
    }

    public function getError()
    {
        return $this->error;
    }

    public function save($destination, $flags = self::APPEND_LOCAL_NAME)
    {
        if($flags & self::APPEND_LOCAL_NAME) {
            $destination .= DIRECTORY_SEPARATOR . $this->getFileName();
        }
        if($flags & self::OVERWRITE === 0) {
            if(is_file($destination)) {
                return false;
            }
        }

        return move_uploaded_file($this->getTempName(), $destination);
    }
}
