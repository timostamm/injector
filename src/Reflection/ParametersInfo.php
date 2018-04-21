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
        return $this->hasType($index) && $this->typeBuiltin[ $index ];
    }

    public function isRequired(int $index):bool
    {
        return ! $this->optional[ $index ];
    }


    public function findIndex(string $name):?int
    {
        $index = array_search($name, $this->name, true);
        return $index === false ? null : $index;
    }

    public function findName(int $index):?string
    {
        return $this->name[$index] ?? null;
    }

    public function isVariadic(int $index = -1):bool
    {
        if ($index === -1) {
            return $this->variadic;
        }
        if ($index < 0 || $index >= $this->count()) {
            throw new \OutOfRangeException(sprintf('%s is out of range.', $index));
        }
        return $index === $this->count()-1 && $this->variadic;
    }

    public function count():int
    {
        return count($this->name);
    }


}