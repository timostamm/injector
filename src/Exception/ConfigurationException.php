<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 09:01
 */

namespace TS\DependencyInjection\Exception;
use LogicException;


class ConfigurationException extends LogicException implements InjectorException
{

    public static function cannotUseParametersForSingleton(string $className): ConfigurationException
    {
        $msg = sprintf('The class %s is registered as a singleton and cannot be instantiated with parameters. You have to provide the parameters when registering the singleton.', $className);
        return new ConfigurationException($msg);
    }

    public static function cannotRegisterParamsIsSingleton(string $className): ConfigurationException
    {
        $msg = sprintf('Cannot register parameters, class %s is instantiated as a singleton.', $className);
        return new ConfigurationException($msg);
    }

    public static function aliasNotPossibleHasSingletonInstance(string $className): ConfigurationException
    {
        $msg = sprintf('Cannot alias %s, is instantiated as a singleton.', $className);
        return new ConfigurationException($msg);
    }

    public static function singletonAlreadyInstantiatedCannotRegister(string $className): ConfigurationException
    {
        $msg = sprintf('Cannot register %s as a singleton, it already is registered and instantiated.', $className);
        return new ConfigurationException($msg);
    }

    public static function singletonMissingInstance(string $className): ConfigurationException
    {
        $msg = sprintf('Expected to have a singleton instance for class %s but it was not found.', $className);
        return new ConfigurationException($msg);
    }

    public static function singletonNotRegistered(string $className): ConfigurationException
    {
        $msg = sprintf('%s is not registered as a singleton.', $className);
        return new ConfigurationException($msg);
    }

}