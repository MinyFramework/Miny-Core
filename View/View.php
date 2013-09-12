<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\View;

use Miny\View\Exceptions\ViewMissingVariablesException;

class View
{
    //function: {stg:$a,"b"}
    //template: {template:valami}
    //list: {list:sablon:$tomb}
    private static $variable_pattern = '/\{$(.*?)\}/Sus';
    private static $function_pattern = '/\{([^$]*?)\}/Sus'; //incomplete - this is the most complex
    private static $include_pattern = '/\{template:(.*?)\}/Sus';
    private static $list_pattern = '/\{list:(.*?):$(.*?)\}/Sus';
    private $variable_placeholders = array();
    private $function_placeholders = array();
    private $include_placeholders = array();
    private $list_placeholders = array();
    private $loader;
    private $template;
    private $helpers;

    public function __construct($template, ViewLoader $loader)
    {
        $this->template = $template;
        $this->loader = $loader;
        $this->helpers = $loader->getHelpers();
        $this->collectPlaceholders();
    }

    public function getTemplate()
    {
        return $this->template;
    }

    protected function collectPlaceholders()
    {
        preg_match_all(self::$variable_pattern, $this->template, $this->variable_placeholders);
        preg_match_all(self::$function_pattern, $this->template, $this->function_placeholders);
        preg_match_all(self::$include_pattern, $this->template, $this->include_placeholders);
        preg_match_all(self::$list_pattern, $this->template, $this->list_placeholders);
    }

    private function getArrayItem(array $array, $key)
    {
        if (!isset($array[$key])) {
            $this->raiseMissingVariableException($key);
        }
        return $array[$key];
    }

    private function getTemplateFunctionParameterValue($var, array $variables)
    {
        $len = strlen($var);
        if ($len == 0) {
            return NULL;
        }
        if ($var[0] == '"' && $var[$len - 1] == '"') {
            return substr($var, 1, $len - 2);
        }
        return $this->getArrayItem($variables, $var);
    }

    private function raiseMissingVariableException($variable, $previous = NULL)
    {
        if (is_array($variable)) {
            $ex = sprintf('Could not render view. Missing variables: %s', implode(', ', $variable));
        } else {
            $ex = sprintf('Could not render view. Variables "%s" is not set.', $variable);
        }
        throw new ViewMissingVariablesException($ex, 0, $previous);
    }

    public function render(array $variables = array())
    {
        $array_diff = array_diff_key($this->variable_placeholders, $variables);
        if (!empty($array_diff)) {
            $this->raiseMissingVariableException($array_diff);
        }

        $replaces = array();
        foreach ($this->variable_placeholders as $placeholder) {
            list($key, $name) = $placeholder;
            $replaces[$key] = $this->getArrayItem($variables, $name);
        }

        foreach ($this->function_placeholders as $placeholder) {
            $key = array_shift($placeholder);
            $function = array_shift($placeholder);
            $arguments = array();
            foreach ($placeholder as $value) {
                $arguments[] = $this->getTemplateFunctionParameterValue($value, $variables);
            }
            $replaces[$key] = call_user_func_array(array($this->helpers, $function), $arguments);
        }
        foreach ($this->include_placeholders as $placeholder) {
            list($key, $template) = $placeholder;

            if ($template[0] == '$') {
                $template = $this->getArrayItem($variables, substr($template, 1));
            }

            $replaces[$key] = $this->loader->getView($template)->render($variables);
        }
        foreach ($this->list_placeholders as $placeholder) {
            list($key, $template, $array) = $placeholder;

            $view = $this->loader->getView($template);
            $temp = array_map(array($view, 'render'), $this->getArrayItem($variables, $array));

            $replaces[$key] = implode('', $temp);
        }
        return str_replace(array_keys($replaces), $replaces, $this->template);
    }

    public function renderMultiple(array $variables = array())
    {
        array_walk($variables, array($this, 'render'));
        return implode('', $variables);
    }

}
