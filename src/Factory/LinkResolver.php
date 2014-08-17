<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Factory;

class LinkResolver
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

    /**
     * @inheritdoc
     */
    public function resolveReferences($argument)
    {
        if (is_array($argument)) {
            //If $argument is an array, resolve all values recursively
            return array_map([$this, 'resolveReferences'], $argument);
        }

        //direct injection for non-string values and characters
        if (!is_string($argument) || strlen($argument) < 2) {
            return $argument;
        }

        if (strpos($argument, '{@') !== false) {
            $argument = $this->parameterContainer->resolveLinksInString($argument);
        }

        //see if $argument is a reference to something
        switch ($argument[0]) {
            case '@':
                $argument = $this->resolveReferences(
                    $this->parameterContainer->offsetGet(substr($argument, 1))
                );
                break;

            case '\\':
                $argument = substr($argument, 1);
                break;
        }

        return $argument;
    }
}
