<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 19.04.18
 * Time: 00:07
 */

namespace TS\DependencyInjection;

use ReflectionMethod;
use ReflectionParameter;
use ReflectionType;
use RuntimeException;


class InjectorException extends RuntimeException
{

    const CODE_MISSING_PARAMETER = 100;
    const CODE_WRONG_PARAMETER_TYPE = 200;

    private $parameter;

    public function getParameter():?ReflectionParameter
    {
        return $this->parameter;
    }

    protected function setParameter(ReflectionParameter $value):void
    {
        $this->parameter = $value;
    }

    public function getParameterName():?string
    {
        return $this->getParameter() ? $this->getParameter()->getName() : null;
    }

    public function getArgumentName():?string
    {
        return $this->getParameterName() ? '$'.$this->getParameterName() : null;
    }


    public static function wrongParameterType($value, ReflectionParameter $param): InjectorException
    {
        $function = self::describeDeclaringFunction($param);
        $actualType = is_object($value) ? get_class($value) : gettype($value);
        $expectedType = self::describeType($param->getType());
        $msg = sprintf('Argument $%s passed to %s must be of the type %s, %s given', $param->getName(), $function, $expectedType, $actualType);
        $ex = new InjectorException($msg);
        $ex->setParameter($param);
        return $ex;
    }


    public static function missingParameter(ReflectionParameter $param): InjectorException
    {
        $function = self::describeDeclaringFunction($param);
        if ($param->hasType()) {
            $type = self::describeType($param->getType());
            $msg = sprintf('Dependency "%s $%s" of "%s" could not be resolved.', $type, $param->getName(), $function);
        } else {
            $msg = sprintf('Missing argument $%s for %s', $param->getName(), $function);
        }
        $ex = new InjectorException($msg);
        $ex->setParameter($param);
        return $ex;
    }


    protected static function describeType(ReflectionType $type): string
    {
        if (!$type->isBuiltin()) {
            return strval($type);
        }
        $t = strval($type);
        if ($t === 'int') {
            return 'integer';
        }
        if ($t === 'bool') {
            return 'boolean';
        }
        return $t;
    }

    protected static function describeDeclaringFunction(ReflectionParameter $param): string
    {
        $function = $param->getDeclaringFunction();
        if ($function instanceof ReflectionMethod) {
            $class = $function->getDeclaringClass();
            if ($function->getName() === '__construct') {
                $str = sprintf('new %s()', $class->getName());
            } else {
                $str = sprintf('%s::%s()', $class->getName(), $function->getName());
            }
        } else {
            $str = sprintf('%s()', $function->getName());
        }
        return $str;
    }

}