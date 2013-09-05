<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\View;

class View
{
    //variable: {$asd}
    //function: {stg:$a,"b"}
    //template: {template:valami}
    //list: {list:sablon:$tomb}
    private static $variable_pattern = '/\{$(.*?)\}/Sus';
    private static $function_pattern = '/\{([^$]*?)\}/Sus';
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

    private function getTemplateFunctionParameterValue($var, array $variables)
    {
        $len = strlen($var);
        if ($len == 0) {
            return NULL;
        }
        if ($var[0] == '"' && $var[$len - 1] == '"') {
            return substr($var, 1, $len - 2);
        }
        if (isset($variables[$var])) {
            return $variables[$var];
        }
        $ex = sprintf('Could not render view. Missing variable: %s', $var);
        throw new ViewMissingVariablesException($ex);
    }

    public function render(array $variables = array())
    {
        $array_diff = array_diff_key($this->variable_placeholders, $variables);
        if (!empty($array_diff)) {
            $ex = sprintf('Could not render view. Missing variables: %s', implode(', ', $array_diff));
            throw new ViewMissingVariablesException($ex);
        }
        //TODO: make sure that keys and values are the same order
        $keys = array_keys($this->variable_placeholders);
        $values = $variables;

        foreach ($this->function_placeholders as $placeholder) {
            $key = array_shift($placeholder);
            $function = array_shift($placeholder);
            $arguments = array();
            foreach ($placeholder as $value) {
                $arguments[] = $this->getTemplateFunctionParameterValue($value, $variables);
            }

            $keys[] = $key;
            $values[] = call_user_func_array(array($this->helpers, $function), $arguments);
        }
        foreach ($this->include_placeholders as $placeholder) {
            list($key, $template) = $placeholder;
            $keys[] = $key;
            $values[] = $this->loader->getView($template)->render($values);
        }
        foreach ($this->list_placeholders as $placeholder) {
            list($key, $template, $array) = $placeholder;

            if (!isset($values[$array])) {
                $ex = sprintf('Could not render view. Missing variable: %s', $array);
                throw new ViewMissingVariablesException($ex);
            }

            $view = $this->loader->getView($template);
            $temp = array_map(array($view, 'render'), $values[$array]);

            $keys[] = $key;
            $values[] = implode('', $temp);
        }
        return str_replace($keys, $values, $this->template);
    }

    public function renderMultiple(array $variables = array())
    {
        array_walk($variables, array($this, 'render'));
        return implode('', $variables);
    }

}
