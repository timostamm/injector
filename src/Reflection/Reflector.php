<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 09:50
 */

namespace TS\DependencyInjection\Reflection;

use Closure;
use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionFunction;
use ReflectionObject;


class Reflector
{


    const FUNCTION = 'function';
    const CLOSURE = 'closure';
    const OBJECT_METHOD = 'object_method';
    const STATIC_CLASS_METHOD = 'static_class_method';


    public function classExists(string $className):bool
    {
        return class_exists($className) || interface_exists($className);
    }


    protected function getClass(string $className):ReflectionClass
    {
        return new ReflectionClass($className);
    }


    public function isClassInstantiable(string $className):bool
    {
        $class = $this->getClass($className);
        return $class->isInstantiable();
    }


    public function isClassAssignable(string $from, string $to):bool
    {
        return is_a($from, $to, true);
    }


    /**
     * Create instance using constructor if applicable.
     * Does not check if class exists or whether it is instantiable.
     */
    public function instantiateClass(string $className, array $args)
    {
        $class = $this->getClass($className);
        $constructor = $class->getConstructor();
        if (empty($constructor)) {
            $instance = $class->newInstanceWithoutConstructor();
        } else {
            $instance = $class->newInstanceArgs($args);
        }
        return $instance;
    }


    public function getConstructorParametersInfo(string $className):ParametersInfo
    {
        $class = $this->getClass($className);
        $constructor = $class->getConstructor();
        $info = new ParametersInfo();
        if (! empty($constructor)) {
            $info->parse($constructor);
        }
        return $info;
    }


    protected function getCallable(callable $callable): ReflectionFunctionAbstract
    {
        $type = $this->getCallableType($callable);
        switch ($type) {
            case self::FUNCTION:
            case self::CLOSURE:
                $function = new ReflectionFunction($callable);
                break;
            case self::OBJECT_METHOD:
                $object = new ReflectionObject($callable[0]);
                $function = $object->getMethod($callable[1]);
                break;
            case self::STATIC_CLASS_METHOD:
                $a = is_array($callable) ? $callable : explode('::', $callable);
                $class = $this->getClass($a[0]);
                $function = $class->getMethod($a[1]);
                break;
            default:
                throw new \LogicException('Unknown callable type: ' . gettype($callable));
        }
        return $function;
    }


    public function getCallableId(callable $callable):string
    {
        if (is_array($callable)) {
            if (is_string($callable[0])) {
                return $callable[0];
            } else {
                return sprintf('%s#%s::%s', get_class($callable[0]), $this->objectId($callable[0]), $callable[1]);
            }
        } else if (is_string($callable)) {
            if (strpos($callable, '::') > 0) {
                return $callable;
            } else {
                return $callable;
            }
        } else if ($callable instanceof Closure) {
            return sprintf('%s#%s', get_class($callable), $this->objectId($callable));
        }
        throw new \LogicException();
    }


    protected function objectId($object):string
    {
        if (function_exists('spl_object_id')) {
            return spl_object_id($object);
        }
        return spl_object_hash($object);
    }


    public function getCallableType(callable $callable):string
    {
        if (is_array($callable)) {
            if (is_string($callable[0])) {
                return self::STATIC_CLASS_METHOD;
            } else {
                return self::OBJECT_METHOD;
            }
        } else if (is_string($callable)) {
            if (strpos($callable, '::') > 0) {
                return self::STATIC_CLASS_METHOD;
            } else {
                return self::FUNCTION;
            }
        } else if ($callable instanceof Closure) {
            return self::CLOSURE;
        } else {
            throw new \LogicException('Unknown callable type: ' . gettype($callable));
        }
    }


    public function getCallableParametersInfo(callable $callable):ParametersInfo
    {
        $ref = $this->getCallable($callable);
        $info = new ParametersInfo();
        $info->parse($ref);
        return $info;
    }


    public static function isBuiltinType(string $type):bool
    {
        return in_array($type, ['bool', 'int', 'float', 'string', 'array', 'resource', 'callable']);
    }


    public static function getType($value):string
    {
        $type = gettype($value);
        switch ($type) {
            case 'boolean':
                return 'bool';
            case 'integer':
                return 'int';
            case 'double':
                return 'float';
            case 'resource':
            case 'resource (closed)':
                return 'resource';
            case 'object':
                return get_class($value);
            case 'NULL':
                return 'null';
            case 'string':
            case 'array':
                return $type;
            default:
                return $type; // resource, resource (closed), NULL, unknown type
        }
    }


    public static function labelForValue($value):string
    {
        $type = gettype($value);
        switch ($type) {
            case 'boolean':
                return $value ? 'true' : 'false';
            case 'integer':
                return strval($value);
            case 'double':
                return sprintf('%F', $value);
            case 'string':
                return sprintf('"%.100s"', $value);
            case 'array':
                return sprintf('array(%s)', count($value));
            case 'object':
                return sprintf('object(%s)', get_class($value));
            default:
                return $type; // resource, resource (closed), NULL, unknown type
        }
    }


}