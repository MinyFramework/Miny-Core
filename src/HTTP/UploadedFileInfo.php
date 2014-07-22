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
}
