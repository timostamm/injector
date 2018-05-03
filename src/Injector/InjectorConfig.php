<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 11:34
 */

namespace TS\DependencyInjection\Injector;


use TS\DependencyInjection\Exception\InjectorConfigException;
use TS\DependencyInjection\Reflection\ParametersInfo;
use TS\DependencyInjection\Reflection\Reflector;


class InjectorConfig
{

    protected $reflector;
    protected $params;
    protected $alias;
    protected $decorators;
    protected $factories;
    protected $singleton;
    protected $singletonInstantiated;
    protected $emptyParams;

    public function __construct(Reflector $reflector)
    {
        $this->reflector = $reflector;
        $this->params = [];
        $this->alias = [];
        $this->decorators = [];
        $this->factories = [];
        $this->singleton = [];
        $this->singletonInstantiated = [];
        $this->emptyParams = new ParametersConfig(new ParametersInfo());
    }


    public function registerClassAlias(string $source, string $replacement): void
    {
        if (! $this->reflector->classExists($source)) {
            throw InjectorConfigException::aliasClassNotFound($source, $source, $replacement);
        }
        if (! $this->reflector->classExists($replacement)) {
            throw InjectorConfigException::aliasClassNotFound($replacement, $source, $replacement);
        }
        if ( ! $this->reflector->isClassInstantiable($replacement) ) {
            throw InjectorConfigException::aliasTargetNotInstantiable($source, $replacement);
        }
        if ( ! $this->reflector->isClassAssignable($replacement, $source) ) {
            throw InjectorConfigException::aliasNotAssignable($source, $replacement);
        }
        if ($this->isSingleton($replacement) && $this->singletonInstantiated[$replacement]) {
            throw InjectorConfigException::aliasHasSingletonInstance($source, $replacement);
        }
        if ($this->isSingleton($source)) {
            throw InjectorConfigException::aliasSourceIsSingleton($source, $replacement);
        }
        if (array_key_exists($source, $this->decorators)) {
            throw InjectorConfigException::aliasSourceIsDecorated($source, $replacement);
        }
        $this->alias[$source] = $replacement;
    }



    public function registerClassParameters(string $className, array $params): void
    {
        if (! $this->reflector->classExists($className)) {
            throw InjectorConfigException::parameterClassNotFound($className);
        }
        if ( ! $this->reflector->isClassInstantiable($className) ) {
            throw InjectorConfigException::parameterClassNotInstantiable($className);
        }
        if ($this->isSingleton($className) && $this->singletonInstantiated[$className]) {
            throw InjectorConfigException::cannotRegisterParamsIsSingleton($className);
        }
        $this->params[$className] = $this->parseClassParameters($className, $params);
    }


    public function registerClassDecorator(string $className, callable $decorator):void
    {
        if (! $this->reflector->classExists($className)) {
            throw InjectorConfigException::decoratorClassNotFound($className);
        }
        if (array_key_exists($className, $this->alias)) {
            throw InjectorConfigException::decoratorAliased($className, $this->alias[$className]);
        }
        if ($this->isSingleton($className) && $this->singletonInstantiated[$className]) {
            throw InjectorConfigException::cannotRegisterDecoratorIsSingleton($className);
        }
        if (! array_key_exists($className, $this->decorators)) {
            $this->decorators[$className] = [];
        }
        $this->decorators[$className][] = $decorator;
    }


    public function getClassDecorators(string $className):array
    {
        return $this->decorators[$className] ?? [];
    }



    public function registerClassFactory(string $className, callable $factory):void
    {
        if (! $this->reflector->classExists($className)) {
            throw InjectorConfigException::factoryClassNotFound($className);
        }
        if (array_key_exists($className, $this->alias)) {
            throw InjectorConfigException::factoryAliased($className, $this->alias[$className]);
        }
        if ($this->isSingleton($className) && $this->singletonInstantiated[$className]) {
            throw InjectorConfigException::cannotRegisterFactoryIsSingleton($className);
        }

        $returnType = $this->reflector->getCallableReturnType($factory);
        if (is_null($returnType)) {
        } else if ($returnType === 'void') {
            throw InjectorConfigException::factoryReturnsWrongType($className, $returnType);
        } else if (! $this->reflector->isClassAssignable($returnType, $className)) {
            throw InjectorConfigException::factoryReturnsWrongType($className, $returnType);
        }

        $this->factories[$className] = $factory;
    }

    public function hasClassFactory(string $className):bool
    {
        return array_key_exists($className, $this->factories);
    }

    public function getClassFactory(string $className):callable
    {
        return $this->factories[$className];
    }


    public function registerSingleton(string $className): void
    {
        if ($this->isSingleton($className) && $this->singletonInstantiated[$className]) {
            throw InjectorConfigException::singletonAlreadyInstantiatedCannotRegister($className);
        }
        if (! $this->reflector->classExists($className)) {
            throw InjectorConfigException::singletonClassNotFound($className);
        }
        if (!$this->reflector->isClassInstantiable($className)) {
            throw InjectorConfigException::singletonNotInstantiable($className);
        }
        if (array_key_exists($className, $this->alias)) {
            throw InjectorConfigException::singletonAliased($className, $this->alias[$className]);
        }
        $this->singleton[$className] = $className;
        $this->singletonInstantiated[$className] = false;
    }


    public function setSingletonInstantiated(string $className):void
    {
        if (!$this->isSingleton($className)) {
            throw new \LogicException(sprintf('Cannot mark singleton %s as instantiated, is not registered as singleton.', $className));
        }
        $this->singletonInstantiated[$className] = true;
    }


    public function isSingleton(string $className): bool
    {
        return array_key_exists($className, $this->singleton);
    }


    public function resolveClassAlias(string $className): string
    {
        return array_key_exists($className, $this->alias)
            ? $this->alias[$className]
            : $className;
    }

    public function getClassParameters(string $className): ParametersConfig
    {
        return $this->params[$className] ?? $this->emptyParams;
    }


    public function parseClassParameters(string $className, array $params = null): ParametersConfig
    {
        if (empty($params)) {
            return $this->emptyParams;
        }
        $info = $this->reflector->getConstructorParametersInfo($className);
        $config = new ParametersConfig($info);
        $config->parse($params);
        return $config;
    }

    public function parseCallableParameters(callable $callable, array $params = null): ParametersConfig
    {
        if (empty($params)) {
            return $this->emptyParams;
        }
        $info = $this->reflector->getCallableParametersInfo($callable);
        $config = new ParametersConfig($info);
        $config->parse($params);
        return $config;
    }

}