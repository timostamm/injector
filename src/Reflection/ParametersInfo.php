<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 20:12
 */

namespace TS\DependencyInjection\Reflection;

use ReflectionFunctionAbstract;

class ParametersInfo
{

    private $name;
    private $index;
    private $optional;
    private $allowsNull;
    private $type;
    private $typeBuiltin;
    private $defaultValue;
    private $variadic;

    public function __construct()
    {
        $this->name = [];
        $this->index = [];
        $this->type = [];
        $this->typeBuiltin = [];
        $this->allowsNull = [];
        $this->optional = [];
        $this->defaultValue = [];
        $this->variadic = false;
    }

    public function parse(ReflectionFunctionAbstract $functionAbstract): void
    {
        $this->variadic = $functionAbstract->isVariadic();
        foreach ($functionAbstract->getParameters() as $param) {
            $name = $param->getName();
            $this->name[count($this->name)] = $name;
            $this->index[$name] = count($this->index);
            $this->optional[$name] = $param->isOptional();
            $this->allowsNull[$name] = $param->allowsNull();
            $type = $param->getType();
            if ($param->isDefaultValueAvailable()) {
                $this->defaultValue[$name] = $param->getDefaultValue();
            }
            if (is_null($type)) {
                $this->type[$name] = null;
                $this->typeBuiltin[$name] = false;
            } else {
                $this->type[$name] = strval($type);
                $this->typeBuiltin[$name] = $type->isBuiltin();
            }
        }
    }

    public function isTypeAssignable(string $type, string $name):bool
    {
        $target = $this->getType($name);
        if (is_null($target)) {
            return true;
        }
        if ($type === $target) {
            return true;
        }
        if (Reflector::isBuiltinType($target) || Reflector::isBuiltinType($type)) {
            return false;
        }
        if (! is_subclass_of($type, $target, true)) {
            return false;
        }
        return true;
    }

    public function isValueAssignable($value, string $name):bool
    {
        if ( is_null($value) && $this->allowsNull($name) ) {
            return false;
        }
        $type = $this->getType($name);
        switch ($type) {
            case null:
                return true;
            case 'bool':
                return is_bool($value);
            case 'int':
            case 'float':
                return is_numeric($value);
            case 'string':
                return is_string($value);
            case 'array':
                return is_array($value);
            case 'resource':
                return is_resource($value);
            case 'callable':
                return is_callable($value);
            default:
                return is_a($value, $type);
        }
    }



    public function getNames():array
    {
        return $this->name;
    }


    public function includes(string $name):bool
    {
        return array_key_exists($name, $this->index);
    }


    public function indexOf(string $name):int
    {
        if (! array_key_exists($name, $this->index)) {
            throw new \OutOfRangeException(sprintf('Parameter %s is undefined.', $name));
        }
        return $this->index[$name];
    }


    public function findName(int $index):?string
    {
        return $this->name[$index] ?? null;
    }


    public function getType(string $name):?string
    {
        if (! array_key_exists($name, $this->index)) {
            throw new \OutOfRangeException(sprintf('Parameter %s is undefined.', $name));
        }
        return $this->type[$name];
    }


    public function isTypeBuiltin(string $name):bool
    {
        if (! array_key_exists($name, $this->index)) {
            throw new \OutOfRangeException(sprintf('Parameter %s is undefined.', $name));
        }
        return $this->typeBuiltin[$name];
    }


    public function isRequired(string $name):bool
    {
        if (! array_key_exists($name, $this->index)) {
            throw new \OutOfRangeException(sprintf('Parameter %s is undefined.', $name));
        }
        return ! $this->optional[$name];
    }


    public function allowsNull(string $name):bool
    {
        if (! array_key_exists($name, $this->index)) {
            throw new \OutOfRangeException(sprintf('Parameter %s is undefined.', $name));
        }
        return $this->allowsNull[$name];
    }


    public function getDefaultValue(string $name)
    {
        if (! array_key_exists($name, $this->index)) {
            throw new \OutOfRangeException(sprintf('Parameter %s is undefined.', $name));
        }
        return $this->defaultValue[$name] ?? null;
    }


    public function isVariadic(string $name):bool
    {
        if (! array_key_exists($name, $this->index)) {
            throw new \OutOfRangeException(sprintf('Parameter %s is undefined.', $name));
        }
        if (! $this->variadic) {
            return false;
        }
        $index = $this->indexOf($name);
        return $index === $this->count() -1;
    }

    public function hasVariadic():bool
    {
        return $this->variadic;
    }

    public function getVariadic():?string
    {
        if (! $this->variadic) {
            return null;
        }
        return $this->name[ count($this->index) -1 ];
    }


    public function count():int
    {
        return count($this->index);
    }


}