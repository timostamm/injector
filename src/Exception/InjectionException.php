<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 09:31
 */

namespace TS\DependencyInjection\Exception;
use ReflectionClass;
use RuntimeException;


class InjectionException extends RuntimeException implements InjectorException
{

    public static function classNotFound(string $className, string $aliasedFrom=null):InjectionException
    {
        if (empty($aliasedFrom)) {
            $msg = sprintf('Class %x (alias for %s) does not exist, cannot instantiate it.', $className, $aliasedFrom);
        } else {
            $msg = sprintf('Class %x does not exist, cannot instantiate it.', $className);
        }
        return new InjectionException($msg);
    }


    public static function classNotInstantiable(string $className, string $aliasedFrom=null): InjectionException
    {
        $reflector = new ReflectionClass($className);
        $what = empty($aliasedFrom) ? $className : sprintf('% aliased from %s', $className, $aliasedFrom);
        if ($reflector->isInterface()) {
            $msg = sprintf('Cannot instantiate the interface %s. Consider creating an alias to a concrete implementation.', $what);
        } else if ($reflector->isAbstract()) {
            $msg = sprintf('Cannot instantiate the abstract class %s. Consider creating an alias to a concrete implementation.', $what);
        } else {
            $msg = sprintf('Cannot instantiate %s.', $what);
        }
        return new InjectionException($msg);
    }
}