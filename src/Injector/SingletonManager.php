<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 08:53
 */

namespace TS\DependencyInjection\Injector;

use TS\DependencyInjection\Exception\ConfigurationException;


// TODO hier keine parameters speichern
class SingletonManager
{

    private $entries;

    public function __construct()
    {
        $this->entries = [];
    }

    public function register(string $className, array $parameterConfig = null):void
    {
        if ($this->hasInstance($className)) {
            throw ConfigurationException::singletonAlreadyInstantiatedCannotRegister($className);
        }
        $this->entries[$className] = [
            'params' => $parameterConfig,
            'instance' => null
        ];
    }

    public function isRegistered(string $className):bool
    {
        return array_key_exists($className, $this->entries);
    }

    public function getParameters(string $className):array
    {
        if (! $this->isRegistered($className)) {
            throw ConfigurationException::singletonNotRegistered($className);
        }
        return $this->entries[$className]['params'] ?? [];
    }

    public function setInstanceIfApplicable(string $className, $instance):void
    {
        if ($this->isRegistered($className) && ! $this->hasInstance($className)) {
            $this->entries[$className]['instance'] = $instance;
        }
    }

    public function hasInstance(string $className):bool
    {
        return $this->isRegistered($className) && is_object($this->entries[$className]['instance']);
    }

    public function getInstance(string $className)
    {
        if (! $this->hasInstance($className)) {
            throw ConfigurationException::singletonMissingInstance($className);
        }
        return $this->entries[$className]['instance'];
    }


}