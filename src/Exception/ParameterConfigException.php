<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 09:01
 */

namespace TS\DependencyInjection\Exception;

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

    public static function spreadValueNotIterable(string $name, $value): self
    {
        $msg = sprintf('Cannot spread value of type %s for parameter ...$%s, value must be iterable.', gettype($value), $name);
        return new self($msg);
    }


    public static function cannotSpreadNonVariadic(string $name): self
    {
        $msg = sprintf('Cannot spread non-variadic parameter $%s.', $name);
        return new self($msg);
    }


    public static function cannotHintVariadic($key): self
    {
        if (is_string($key)) {
            $msg = sprintf('Cannot hint variadic parameter ...$%s.', $key);
        } else {
            $msg = sprintf('Cannot hint variadic parameter ...#%s.', $key);
        }
        return new self($msg);
    }


    public static function hintInvalid($key, $notAStringValue): self
    {
        if (is_string($key)) {
            $msg = sprintf('Parameter hint $%s must be a class name or builtin type, got %s.', $key, gettype($notAStringValue));
        } else {
            $msg = sprintf('Parameter hint #%s must be a class name or builtin type, got %s.', $key, gettype($notAStringValue));
        }
        return new self($msg);
    }


    public static function redundantHint($key, $hint): self
    {
        if (is_string($key)) {
            $msg = sprintf('Parameter hint $%s as %s is redundant.', $key, $hint);
        } else {
            $msg = sprintf('Parameter hint #%s as %s is redundant.', $key, $hint);
        }
        return new self($msg);
    }


    public static function alreadyHinted($key, $hint, string $type): self
    {
        if (is_string($key)) {
            $msg = sprintf('Cannot hint parameter $%s as %s, the type is not assignable to the existing parameter type %s.', $key, $hint, $type);
        } else {
            $msg = sprintf('Cannot hint parameter #%s as %s, the type is not assignable to the existing parameter type %s.', $key, $hint, $type);
        }
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