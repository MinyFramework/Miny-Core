<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\TemporaryFiles;

class TemporaryFileManager
{
    private $tempDirectoryRoot;

    private $moduleStack = [];
    private $currentModule;

    public function __construct($tempDirectoryRoot)
    {
        $this->tempDirectoryRoot = rtrim($tempDirectoryRoot, '/') . '/';
        $this->ensureDirectoryExists($this->tempDirectoryRoot);
    }

    public function enterModule($module)
    {
        $this->moduleStack[] = $this->currentModule;
        $this->currentModule = $module . '/';
    }

    public function exitModule()
    {
        $this->currentModule = array_pop($this->moduleStack);
    }

    public function load($file)
    {
        $file = $this->getFileName($file);
        $this->ensureDirectoryIsSafe($file);

        if (!is_file($file)) {
            throw new \InvalidArgumentException("File {$file} is not found.");
        }

        return include $file;
    }

    public function read($file)
    {
        $file = $this->getFileName($file);
        $this->ensureDirectoryIsSafe($file);

        if (!is_file($file)) {
            throw new \InvalidArgumentException("File {$file} is not found.");
        }

        return file_get_contents($file);
    }

    public function save($file, $contents)
    {
        $file      = $this->getFileName($file);
        $directory = dirname($file);

        $this->ensureDirectoryIsSafe($directory);
        $this->ensureDirectoryExists($directory);

        file_put_contents($file, $contents);
    }

    public function exists($file)
    {
        $file = $this->getFileName($file);
        $this->ensureDirectoryIsSafe(dirname($file));

        return is_file($file);
    }

    public function getFileInfo($file)
    {
        $file = $this->getFileName($file);
        $this->ensureDirectoryIsSafe(dirname($file));

        return new \SplFileInfo($file);
    }

    private function getFileName($file)
    {
        return strtr($this->tempDirectoryRoot . $this->currentModule . $file, '\\', '/');
    }

    /**
     * @param $directory
     *
     * @throws \InvalidArgumentException
     */
    private function ensureDirectoryIsSafe($directory)
    {
        if (
            $directory === '..'
            || strpos($directory, '../') !== false
            || strpos($directory, '/..') !== false
        ) {
            throw new \InvalidArgumentException("File path must not contain ..");
        }
    }

    /**
     * @param $directory
     *
     * @throws \UnexpectedValueException
     */
    private function ensureDirectoryExists($directory)
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0777, true)) {
                throw new \UnexpectedValueException("Directory {$directory} could not be created");
            }
        }
    }
}
