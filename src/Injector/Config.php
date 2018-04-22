<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 11:34
 */

namespace TS\DependencyInjection\Injector;


use TS\DependencyInjection\ParametersConfigTest;
use TS\DependencyInjection\Reflection\ParametersInfo;
use TS\DependencyInjection\Reflection\Reflector;

class Config
{

    protected $reflector;
    protected $params;
    protected $alias;
    protected $singleton;
    protected $emptyParams;

    public function __construct(Reflector $reflector)
    {
        $this->reflector = $reflector;
        $this->params = [];
        $this->alias = [];
        $this->singleton = [];
        $this->emptyParams = new ParametersConfig(new ParametersInfo());
    }


    public function registerAlias(string $from, string $to):void
    {
        $this->aliases[$from] = $to;
    }


    public function registerSingleton(string $className):void
    {
        $this->singleton[$className] = $className;
    }


    public function registerParameters(string $className, array $params):void
    {
        // TODO like singletons: already registered + instantiated? throw exception
        $info = $this->reflector->getConstructorParametersInfo($className);
        $config = new ParametersConfig($info);
        $config->parse($params);
        $this->params[ $className ] = $config;
    }


    public function isSingleton(string $className):bool
    {
        return array_key_exists($className, $this->singleton);
    }


    public function getParameters(string $className):ParametersConfig
    {
        return $this->params[$className] ?? $this->emptyParams;
    }


    public function resolveClass(string $className):string
    {
        return array_key_exists($className, $this->alias)
            ? $this->alias[ $className ]
            : $className;
    }


}