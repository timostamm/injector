<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 09:01
 */

namespace TS\DependencyInjection\Exception;

use RuntimeException;
use TS\DependencyInjection\Reflection\ParametersInfo;
use TS\DependencyInjection\Reflection\Reflector;


class ArgumentListException extends RuntimeException implements InjectorException
{

    public static function missingValues(array $names): self
    {
        $msg = sprintf('Missing parameters $%s.', join(', $', $names));
        return new self($msg);
    }


    public static function parameterNotFound(string $name): self
    {
        $msg = sprintf('Parameter $%s does not exist.', $name);
        return new self($msg);
    }


    public static function valueNotAssignable(string $name, $value, ParametersInfo $info): self
    {
        $expectedType = $info->getType($name);
        $actualType = Reflector::getType($value);
        $msg = sprintf('Expected %s for parameter $%s, got %s instead.', $expectedType, $name, $actualType);
        return new self($msg);
    }


}