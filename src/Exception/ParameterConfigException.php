<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 09:01
 */

namespace TS\DependencyInjection\Exception;

use TS\DependencyInjection\Reflection\ParametersInfo;
use TS\DependencyInjection\Reflection\Reflector;


class ParameterConfigException extends ConfigurationException
{


    public static function hintAndValueCollision(string $hintBy, string $valueBy, $value, $key): self
    {
        $p = (is_string($key) ? '$' : '#') . $key;
        if (is_string($key)) {
            $msg = sprintf('The parameter %s is ambiguously configured with the value %s and the alias %s.', $p, Reflector::labelForValue($value), $hintBy);
        } else {
            $msg = sprintf('The parameter %s is ambiguously configured with the value %s and the alias %s.', $p, Reflector::labelForValue($value), $hintBy);
        }
        return new self($msg);
    }


    public static function cannotAlias($a, $b, $param): self
    {
        $msg = sprintf('The parameter $%s is ambiguously aliased by a) %s and b) %s.', $param, $a, $b);
        return new self($msg);
    }

    public static function instanceParameterNotFound($class, $instance): self
    {
        $msg = sprintf('Cannot set instances for %s::class => %s. No matching parameter found.', $class, Reflector::labelForValue($instance));
        return new self($msg);
    }

    public static function aliasParameterNotFound($aliasFrom, $aliasTo): self
    {
        $msg = sprintf('Cannot apply alias %s::class => %s::class. No matching parameter found.', $aliasFrom, $aliasTo);
        return new self($msg);
    }

    public static function duplicateHint($a, $b, $param): self
    {
        $msg = sprintf('The parameter $%s is ambiguously aliased by a) %s and b) %s.', $param, $a, $b);
        return new self($msg);
    }

    public static function duplicateParameter($a, $b): self
    {
        $msg = sprintf('Parameters %s and %s refer to the same position.', $a, $b);
        return new self($msg);
    }

    public static function nullValueNotAllowed(string $by): self
    {
        $msg = sprintf('Parameter %s does not allow null.', $by);
        return new self($msg);
    }

    public static function valueNotAssignable(string $name, $value, ParametersInfo $info): self
    {
        $expectedType = $info->getType($name);
        $actualType = Reflector::getType($value);
        $msg = sprintf('Expected %s for parameter $%s, got %s instead.', $expectedType, $name, $actualType);
        return new self($msg);
    }


    public static function spreadValueNotIterable(string $name, $value): self
    {
        $actualType = Reflector::getType($value);
        $msg = sprintf('Cannot spread value of type %s for parameter ...$%s, value must be iterable.', $actualType, $name);
        return new self($msg);
    }


    public static function spreadParamNotVariadic(string $name): self
    {
        $msg = sprintf('Cannot spread parameter $%s, it is not a rest parameter.', $name);
        return new self($msg);
    }


    public static function cannotHintVariadic(string $name): self
    {
        $msg = sprintf('Cannot hint variadic parameter ...$%s.', $name);
        return new self($msg);
    }


    public static function hintInvalid($key, $notAStringValue): self
    {
        $actualType = Reflector::getType($notAStringValue);
        if (is_string($key)) {
            $msg = sprintf('Parameter hint $%s must be a class name or builtin type, got %s.', $key, $actualType);
        } else {
            $msg = sprintf('Parameter hint #%s must be a class name or builtin type, got %s.', $key, $actualType);
        }
        return new self($msg);
    }


    public static function redundantHint(string $parameterName, string $hintType): self
    {
        $msg = sprintf('Parameter hint $%s as %s is redundant.', $parameterName, $hintType);
        return new self($msg);
    }


    public static function hintNotAssignable(string $parameterName, string $hintType, string $existingType): self
    {
        $msg = sprintf('Cannot hint parameter $%s as %s, the type is not assignable to the existing parameter type %s.', $parameterName, $hintType, $existingType);
        return new self($msg);
    }

    public static function parameterKeyInvalid($key, $value): self
    {
        if (is_string($key)) {
            $msg = sprintf('The parameter configuration array contains the unrecognized key "%s". Possible definitions are: \'$pdo\' => $myPdo, \'#0\' => $myPdo, \'hint $pdo\' => PDO::class, \'hint #0\' => PDO::class, MyInterface::class => MyImplementation::class, MyInterface::class => $myInstance.', $key);
        } else {
            $msg = sprintf('The parameter configuration array contains the numerical index %s. If you want to provide a value for an argument at a specific position, use "#1" => $value.', $key);
        }
        return new self($msg);
    }


    public static function hintParameterNotFound(string $hintExpr, ParametersInfo $info): self
    {
        $msg = sprintf('Parameter for %s not found.', $hintExpr);
        return new self($msg);
    }

    public static function parameterNameNotFound(string $name, ParametersInfo $info): self
    {
        $msg = sprintf('Parameter $%s does not exist.', $name);
        return new self($msg);
    }

    public static function parameterNotFound($key, bool $forHint = false): self
    {
        $h = $forHint ? 'hint ' : '';
        if (is_string($key)) {
            $msg = sprintf('Parameter %s$%s does not exist.', $h, $key);
        } else {
            $msg = sprintf('Parameter %s#%s is out of range.', $h, $key);
        }
        return new self($msg);
    }


    public static function tooManyParameters(int $maxCount, int $actualCount): self
    {
        $msg = sprintf('You provided %s parameters, but only %s are available.', $actualCount, $maxCount);
        return new self($msg);
    }


}