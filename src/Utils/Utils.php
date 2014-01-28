<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Miny\Utils;

use InvalidArgumentException;
use Miny\Utils\Exceptions\AssertationException;
use ReflectionClass;

/**
 * @author Dániel Buga
 */
class Utils
{

    /**
     * Asserts that $expression is true or throws an exception.
     *
     * @param mixed $expression
     * @param string $on_failure
     * @throws AssertationException
     */
    public static function assert($expression, $on_failure = 'Assertation failed')
    {
        if (!$expression) {
            throw new AssertationException($on_failure);
        }
    }

    /**
     * @param string $class
     * @param array $arguments
     *
     * @return object
     *
     * @throws InvalidArgumentException when the class does not exist.
     */
    public static function instantiate($class, array $arguments = array())
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException('Class not found: ' . $class);
        }
        switch (count($arguments)) {
            case 0:
                return new $class;

            case 1:
                return new $class(current($arguments));

            case 2:
                list($arg1, $arg2) = $arguments;
                return new $class($arg1, $arg2);

            case 3:
                list($arg1, $arg2, $arg3) = $arguments;
                return new $class($arg1, $arg2, $arg3);

            case 4:
                list($arg1, $arg2, $arg3, $arg4) = $arguments;
                return new $class($arg1, $arg2, $arg3, $arg4);

            default:
                $ref = new ReflectionClass($class);
                return $ref->newInstanceArgs($arguments);
        }
    }
}
