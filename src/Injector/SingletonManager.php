<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 08:53
 */

namespace TS\DependencyInjection\Injector;


class SingletonManager
{

    private $instances;

    public function __construct()
    {
        $this->instances = [];
    }

    public function setInstance(string $className, $instance): void
    {
        if ($this->hasInstance($className)) {
            throw new \LogicException();
        }
        $this->instances[$className] = $instance;
    }

    public function hasInstance(string $className): bool
    {
        return array_key_exists($className, $this->instances);
    }

    public function getInstance(string $className)
    {
        if (!$this->hasInstance($className)) {
            throw new \LogicException();
        }
        return $this->instances[$className];
    }


}