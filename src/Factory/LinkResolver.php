<?php

namespace Miny\Factory;

class LinkResolver extends AbstractLinkResolver
{

    /**
     * @var ParameterContainer
     */
    private $parameterContainer;

    /**
     * @param ParameterContainer $parameterContainer
     */
    public function __construct(ParameterContainer $parameterContainer)
    {
        $this->parameterContainer = $parameterContainer;
    }

    public function resolveReferences($argument)
    {
        if (is_array($argument)) {
            $return = array();
            foreach ($argument as $k => $arg) {
                $return[$k] = $this->resolveReferences($arg);
            }

            return $return;
        }

        //direct injection for non-string values and characters
        if (!is_string($argument) || strlen($argument) < 2) {
            return $argument;
        }

        if (strpos($argument, '{@') !== false) {
            $argument = $this->parameterContainer->resolveLinks($argument);
        }

        //see if $var is a reference to something
        if ($argument[0] === '@') {
            $value = $this->parameterContainer->offsetGet(substr($argument, 1));

            $argument = $this->resolveReferences($value);
        } elseif ($argument[0] === '\\') {
            $argument = substr($argument, 1);
        }

        return $argument;
    }
}
