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
    private $optional;
    private $allowsNull;
    private $type;
    private $typeBuiltin;
    private $variadic;

    public function __construct()
    {
        $this->name = [];
        $this->type = [];
        $this->typeBuiltin = [];
        $this->allowsNull = [];
        $this->optional = [];
        $this->variadic = false;
    }

    public function parse(ReflectionFunctionAbstract $functionAbstract): void
    {
        $this->variadic = $functionAbstract->isVariadic();
        foreach ($functionAbstract->getParameters() as $param) {
            $this->name[] = $param->getName();
            $this->optional[] = $param->isOptional();
            $this->allowsNull = $param->allowsNull();
            $type = $param->getType();
            if (is_null($type)) {
                $this->type[] = null;
                $this->typeBuiltin[] = false;
            } else {
                $this->type[] = strval($type);
                $this->typeBuiltin[] = $type->isBuiltin();
            }
        }
    }


    public function isValueAssignable($value, int $index):bool
    {
        if ( is_null($value) && $this->allowsNull($index) ) {
            return false;
        }
        $type = $this->getType($index);
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

    public function hasType(int $index):bool
    {
        return $this->getType($index) != null;
    }

    public function getType(int $index):?string
    {
        if ($index < 0 || $index >= $this->count()) {
            throw new \OutOfRangeException(sprintf('%s is out of range.', $index));
        }
        return $this->type[ $index ];
    }

    public function hasBuiltinType(int $index):bool
    {
        if ($index < 0 || $index >= $this->count()) {
            throw new \OutOfRangeException(sprintf('%s is out of range.', $index));
        }
        return $this->hasType($index) && $this->typeBuiltin[ $index ];
    }

    public function isRequired(int $index):bool
    {
        if ($index < 0 || $index >= $this->count()) {
            throw new \OutOfRangeException(sprintf('%s is out of range.', $index));
        }
        return ! $this->optional[ $index ];
    }

    public function allowsNull(int $index):bool
    {
        if ($index < 0 || $index >= $this->count()) {
            throw new \OutOfRangeException(sprintf('%s is out of range.', $index));
        }
        return ! $this->allowsNull[ $index ];
    }

    public function findIndex(string $name):?int
    {
        $index = array_search($name, $this->name, true);
        return $index === false ? null : $index;
    }

    public function findName(int $index):?string
    {
        if ($index < 0 || $index >= $this->count()) {
            throw new \OutOfRangeException(sprintf('%s is out of range.', $index));
        }
        return $this->name[$index] ?? null;
    }

    public function isVariadic(int $index = -1):bool
    {
        if ($index === -1) {
            return $this->variadic;
        }
        if ($this->variadic) {
            return $index >= $this->count() -1;
        }
        if ($index >= $this->count()) {
            throw new \OutOfRangeException(sprintf('%s is out of range.', $index));
        }
        return false;
    }

    public function count():int
    {
        return count($this->name);
    }


}