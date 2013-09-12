<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\View;

class ViewLoader
{
    private static $group_pattern = '/<!-- Template: (.*?) -->(.*?)<!-- End of template: (\\1) -->/musS';
    private $view_dir;
    private $helpers;
    private $views;
    private $default_format;
    private $loaded_files;

    public function __construct($view_dir, $default_format, ViewHelpers $helpers)
    {
        if (!is_dir($view_dir)) {
            $ex = sprintf('Directory not found: %s', $view_dir);
            throw new ViewDirectoryNotFoundException($ex);
        }
        $this->view_dir = $view_dir;
        $this->helpers = $helpers;
        $this->default_format = $default_format;
        $this->views = array();
        $this->loaded_files = array();
    }

    public function getHelpers()
    {
        return $this->helpers;
    }

    protected function fileLoaded($filename, $format = NULL)
    {
        if ($format == NULL) {
            $format = $this->default_format;
        }
        $file = sprintf('%s/%s.%s.tpl', $this->view_dir, $filename, $format);
        return isset($this->loaded_files[$file]);
    }

    protected function getFileContents($filename, $format = NULL)
    {
        if ($format == NULL) {
            $format = $this->default_format;
        }
        $file = sprintf('%s/%s.%s.tpl', $this->view_dir, $filename, $format);
        if (!is_file($file)) {
            return false;
        }
        $this->loaded_files[$file] = 1;
        return file_get_contents($file);
    }

    public function loadGroupFile($filename, $format = NULL)
    {
        if ($this->fileLoaded($filename, $format)) {
            return;
        }
        $file_contents = $this->getFileContents($filename, $format);
        if (!$file_contents) {
            $ex = sprintf('View group file not found with name "%s" and format "%s"', $filename, $format);
            throw new ViewFileNotFoundException($ex);
        }

        $matches = array();
        preg_match_all(self::$group_pattern, $file_contents, &$matches);

        foreach ($matches as $match) {
            list(, $template_name, $template_contents) = $match;
            $this->views[$template_name] = new View($template_contents, $this);
        }
    }

    public function loadFile($filename, $format = NULL)
    {
        if ($this->fileLoaded($filename, $format)) {
            return;
        }
        $file_contents = $this->getFileContents($filename, $format);
        if (!$file_contents) {
            $ex = sprintf('View file not found with name "%s" and format "%s"', $filename, $format);
            throw new ViewFileNotFoundException($ex);
        }
        $this->views[$filename] = new View($file_contents, $this);
    }

    public function getView($view)
    {
        if (!isset($this->views[$view])) {
            $ex = sprintf('View not found: %s', $view);
            throw new ViewFileNotFoundException($ex);
        }
        return $this->views[$view];
    }

}
