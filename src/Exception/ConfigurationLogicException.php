<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 09:01
 */

namespace TS\DependencyInjection\Exception;
use LogicException;


class ConfigurationLogicException extends LogicException implements ConfigurationException
{

    public static function cannotUseParametersForSingleton(string $className): ConfigurationLogicException
    {
        $msg = sprintf('The class %s is registered as a singleton and cannot be instantiated with parameters. You have to provide the parameters when registering the singleton.', $className);
        return new ConfigurationLogicException($msg);
    }


    public static function singletonAlreadyInstantiatedCannotRegister(string $className): ConfigurationLogicException
    {
        $msg = sprintf('Cannot register %s as a singleton, it already is registered and instantiated.', $className);
        return new ConfigurationLogicException($msg);
    }

    public static function singletonMissingInstance(string $className): ConfigurationLogicException
    {
        $msg = sprintf('Expected to have a singleton instance for class %s but it was not found.', $className);
        return new ConfigurationLogicException($msg);
    }

    public static function singletonNotRegistered(string $className): ConfigurationLogicException
    {
        $msg = sprintf('%s is not registered as a singleton.', $className);
        return new ConfigurationLogicException($msg);
    }

}