<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 11:34
 */

namespace TS\DependencyInjection\Injector;


use TS\DependencyInjection\ParametersConfigTest;
use TS\DependencyInjection\Reflection\Reflector;

class ParametersManager
{

    protected $reflector;
    protected $defaults;

    public function __construct(Reflector $reflector)
    {
        $this->reflector = $reflector;
    }


    public function registerClassDefaults($className, array $params):void
    {
        // TODO like singletons: already registered + instantiated? throw exception

        $this->createParametersConfig($className, $params );
    }


    public function createParametersConfig(string $className, array $params = null):void
    {
        $info = $this->reflector->getConstructorParametersInfo($className);
        $config = new ParametersConfig($info);
        if (! empty($params)) {
            $config->parse($params);
        }
        return $config;
    }



}