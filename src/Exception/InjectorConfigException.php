<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 09:01
 */

namespace TS\DependencyInjection\Exception;
use LogicException;


class InjectorConfigException extends LogicException implements InjectorException
{

    public static function parameterClassNotFound(string $className): self
    {
        $msg = sprintf('Cannot register parameters for %s because the class was not found.', $className);
        return new self($msg);
    }

    public static function parameterClassNotInstantiable(string $className): self
    {
        $msg = sprintf('Cannot register parameters for %s because the class is not instantiable.', $className);
        return new self($msg);
    }

    public static function aliasClassNotFound(string $missing, string $source, string $replacement): self
    {
        $msg = sprintf('Cannot register %s as an alias for %s because the class %s was not found.', $replacement, $source, $missing);
        return new self($msg);
    }

    public static function aliasNotAssignable(string $source, string $replacement): self
    {
        $msg = sprintf('Cannot register %s as an alias for %s because the class is not assignable to %s.', $replacement, $source, $source);
        return new self($msg);
    }

    public static function aliasHasSingletonInstance(string $source, string $replacement): self
    {
        $msg = sprintf('Cannot register %s as an alias for %s, because %s is already instantiated as a singleton.', $replacement, $source, $replacement);
        return new self($msg);
    }

    public static function aliasSourceIsSingleton(string $source, string $replacement): self
    {
        $msg = sprintf('Cannot register %s as an alias for %s, because %s is registered as a singleton.', $replacement, $source, $source);
        return new self($msg);
    }

    public static function aliasSourceIsDecorated(string $source, string $replacement): self
    {
        $msg = sprintf('Cannot register %s as an alias for %s, because %s is decorated.', $replacement, $source, $source);
        return new self($msg);
    }

    public static function aliasTargetNotInstantiable(string $source, string $replacement): self
    {
        $msg = sprintf('Cannot register %s as an alias for %s because it is not instantiable.', $replacement, $source);
        return new self($msg);
    }

    public static function cannotRegisterParamsIsSingleton(string $className): self
    {
        $msg = sprintf('Cannot register parameters, class %s is instantiated as a singleton.', $className);
        return new self($msg);
    }

    public static function cannotRegisterDecoratorIsSingleton(string $className): self
    {
        $msg = sprintf('Cannot register decorator, class %s is instantiated as a singleton.', $className);
        return new self($msg);
    }

    public static function singletonAlreadyInstantiatedCannotRegister(string $className): self
    {
        $msg = sprintf('Cannot register %s as a singleton, it already is registered and instantiated.', $className);
        return new self($msg);
    }

    public static function singletonNotInstantiable(string $className): self
    {
        $reflector = new \ReflectionClass($className);
        if ($reflector->isInterface() ) {
            $msg = sprintf('The interface %s is not instantiable and cannot be registered as a singleton.', $className);
        } else {
            $msg = sprintf('The abstract class %s is not instantiable and cannot be registered as a singleton.', $className);
        }
        return new self($msg);
    }

    public static function singletonClassNotFound(string $className): self
    {
        $msg = sprintf('Cannot register %s as a singleton because the class was not found.', $className);
        return new self($msg);
    }

    public static function singletonAliased(string $source, string $replacement): self
    {
        $msg = sprintf('Cannot register %s as a singleton, it already is aliased to %s.', $source, $replacement);
        return new self($msg);
    }


    public static function singletonNotRegistered(string $className): self
    {
        $msg = sprintf('%s is not registered as a singleton.', $className);
        return new self($msg);
    }


    public static function decoratorClassNotFound($className): self
    {
        $msg = sprintf('Cannot decorate %s because the class %s was not found.', $className);
        return new self($msg);
    }

    public static function decoratorAliased($className, $replacement): self
    {
        $msg = sprintf('Cannot decorate %s, class is aliased to %s and would never be decorated.', $className, $replacement);
        return new self($msg);
    }



}