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


    public static function cannotInvoke(string $callableId, \Exception $reason): self
    {
        $msg = sprintf('Unable to call %s: %s', $callableId, $reason->getMessage());
        return new InjectionException($msg, null, $reason);
    }


    public static function cannotInstantiate(string $instantiateId, \Exception $reason): self
    {
        $msg = sprintf('Unable to create %s: %s', $instantiateId, $reason->getMessage());
        return new InjectionException($msg, null, $reason);
    }


    public static function classNotFound(string $className): InjectionException
    {
        $msg = sprintf('Class %s not found.', $className);
        return new InjectionException($msg);
    }


    public static function circularDependency(array $path): self
    {
        $last = $path[count($path) - 1];
        $first = array_search($last, $path, true);
        $parts = array_slice($path, $first);

        $msg = sprintf('Circular dependency detected: %s.', join(' -> ', $parts));
        return new self($msg);
    }

    public static function cannotUseParametersForSingleton(string $className): self
    {
        $msg = sprintf('The class %s is registered as a singleton and cannot be instantiated with parameters. You have to provide the parameters when registering the singleton.', $className);
        return new self($msg);
    }

    public static function classNotInstantiable(string $className): InjectionException
    {
        $reflector = new ReflectionClass($className);
        if ($reflector->isInterface()) {
            $msg = sprintf('Cannot instantiate the interface %s. Consider creating an alias to a concrete implementation.', $className);
        } else if ($reflector->isAbstract()) {
            $msg = sprintf('Cannot instantiate the abstract class %s. Consider creating an alias to a concrete implementation.', $className);
        } else {
            $msg = sprintf('Cannot instantiate %s.', $className);
        }
        return new InjectionException($msg);
    }


}