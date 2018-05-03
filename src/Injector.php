<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 18.04.18
 * Time: 17:44
 */

namespace TS\DependencyInjection;

use TS\DependencyInjection\Exception\ArgumentListException;
use TS\DependencyInjection\Exception\InjectionException;
use TS\DependencyInjection\Injector\ArgumentInspectionInterface;
use TS\DependencyInjection\Injector\ArgumentList;
use TS\DependencyInjection\Injector\InjectorConfig;
use TS\DependencyInjection\Injector\SingletonManager;
use TS\DependencyInjection\Reflection\Reflector;


class Injector implements InjectorInterface, InspectableInjectorInterface
{


    protected $reflector;
    protected $singletons;
    protected $config;
    protected $resolving;


    public function __construct()
    {
        $this->reflector = new Reflector();
        $this->config = new InjectorConfig($this->reflector);
        $this->singletons = new SingletonManager();
        $this->resolving = [];
    }


    /**
     * Create a global alias for an interface.
     *
     * Every time the injector sees this type, it will use the
     * given implementation instead of trying to use the
     * interface.
     *
     * @param string $from class name to replace.
     * @param string $to new class name that should be used instead.
     */
    public function alias(string $from, string $to, array $params = null): void
    {
        if (!empty($params)) {
            $this->config->registerClassParameters($to, $params);
        }
        $this->config->registerClassAlias($from, $to);
    }


    /**
     * Treat the class as a singleton.
     *
     * The injector will only create one instance of this class and
     * return this instance on subsequent calls.
     *
     * Custom parameters provided to instantiate() will be ignored.
     *
     * You can update the parameter config for the singleton by calling
     * singleton() again, unless the class is instantiated.
     *
     * If you already have an instance that you want to provide, use
     * intercept().
     *
     */
    public function singleton(string $className, array $params = null): void
    {
        if (! is_null($params)) {
            $this->config->registerClassParameters($className, $params);
        }
        $this->config->registerSingleton($className);
    }


    /**
     *
     * Set default parameters for a class.
     *
     * @param string $className
     * @param array $params
     */
    public function defaults(string $className, array $params): void
    {
        $this->config->registerClassParameters($className, $params);
    }


    /**
     * Decorate a class.
     *
     * $inj->decorate(Foo::class, function(Foo $foo, LoggerInterface $logger):void {
     *    $foo->setLogger( $logger ) );
     * })
     *
     */
    public function decorate(string $className, callable $decorate): void
    {
        $this->config->registerClassDecorator($className, $decorate);
    }


    /**
     * Intercept a class instantiation.
     * 
     * $inj->factory(Foo::class, function(FooFactory $factory):Foo {
     *   return $factory->create();
     * })
     * 
     * @param string $className
     * @param callable $factory
     */
    public function factory(string $className, callable $factory):void
    {
        $this->config->registerClassFactory($className, $factory);
    }


    /**
     * Call a function or object method and automatically inject
     * dependencies.
     *
     * The injector reads the type hints of all arguments and
     * recursively creates new instances for the dependencies.
     *
     */
    public function invoke(callable $callable, array $params = null)
    {
        return $this->invokeRecursive($callable, $params, []);
    }


    public function inspectInvocation(callable $callable):ArgumentInspectionInterface
    {
        return new ArgumentList($this->reflector->getCallableParametersInfo($callable));
    }


    protected function invokeRecursive($callable, array $params=null, array $path)
    {
        $id = $this->reflector->getCallableId($callable) . '()';

        try {
            // check arguments
            $argumentList = $this->createInvocationArguments($callable, $params);

            // keep dependency path and check for circularity
            $path = array_merge($path, [$id]);
            if (isset($this->resolving[$id])) {
                throw InjectionException::circularDependency($path);
            }
            $this->resolving[$id] = $id;

            // resolve dependencies
            $this->resolveDependencies($argumentList, $path);

        } catch (\Exception $ex) {
            throw InjectionException::cannotInvoke($id, $ex);
        }


        // call
        $result = call_user_func_array($callable, $argumentList->values());


        // this dependency is resolved and must be removed
        unset($this->resolving[$id]);

        return $result;
    }


    protected function createInvocationArguments($callable, array $params = null):ArgumentList
    {
        $list = new ArgumentList($this->reflector->getCallableParametersInfo($callable));
        $list->addConfig($this->config->parseCallableParameters($callable, $params));
        $missingBuiltIns = $list->getMissing(ArgumentList::TYPE_UNTYPED | ArgumentList::TYPE_BUILTIN);
        if (! empty($missingBuiltIns)) {
            throw ArgumentListException::missingValues($missingBuiltIns);
        }
        return $list;
    }



    public function instantiate(string $className, array $params = null)
    {
        if (!$this->reflector->classExists($className)) {
            throw InjectionException::classNotFound($className);
        }
        return $this->instantiateRecursive($className, $params, []);
    }


    public function inspectInstantiation(string $className):ArgumentInspectionInterface
    {
        $resolvedClassName = $this->config->resolveClassAlias($className);
        $argumentList = new ArgumentList($this->reflector->getConstructorParametersInfo($resolvedClassName));
        $argumentList->addConfig($this->config->getClassParameters($resolvedClassName));
        return $argumentList;
    }


    protected function instantiateRecursive(string $className, array $params=null, array $path)
    {
        $resolvedClassName = $this->config->resolveClassAlias($className);
        $id = sprintf('new %s()', $resolvedClassName);
        $factory = null;
        $argumentList = null;

        try {
            // guards
            if ($this->config->isSingleton($resolvedClassName) && !is_null($params)) {
                throw InjectionException::cannotUseParametersForSingleton($resolvedClassName);
            }
            if ( ! $this->config->hasClassFactory($resolvedClassName) && !$this->reflector->isClassInstantiable($resolvedClassName)) {
                throw InjectionException::classNotInstantiable($resolvedClassName);
            }
            // return early if singleton instance present
            if ($this->config->isSingleton($resolvedClassName) && $this->singletons->hasInstance($resolvedClassName)) {
                return $this->singletons->getInstance($resolvedClassName);
            }

            // create argument list
            if ($this->config->hasClassFactory($resolvedClassName)) {
                $factory = $this->config->getClassFactory($resolvedClassName);
                $argumentList = $this->createInvocationArguments($factory);
            } else {
                $argumentList = $this->createInstantiationArguments($resolvedClassName, $params);
            }

            // keep dependency path and check for circularity
            $path = array_merge($path, [$id]);
            if (isset($this->resolving[$id])) {
                throw InjectionException::circularDependency($path);
            }
            $this->resolving[$id] = $id;

            // resolve dependencies
            $this->resolveDependencies($argumentList, $path);

        } catch (\Exception $ex) {
            throw InjectionException::cannotInstantiate($id, $ex);
        }


        // create instance (from factory or class name)
        if ($factory) {
            $instance = call_user_func_array($factory, $argumentList->values());
            if (! is_a($instance, $resolvedClassName)) {
                throw InjectionException::factoryReturnType($resolvedClassName, Reflector::getType($instance));
            }
        } else {
            $instance = $this->reflector->instantiateClass($resolvedClassName, $argumentList->values());
        }


        // decorate it
        $this->callDecorators($className, $instance, $path);


        // remember singleton instance
        if ($this->config->isSingleton($resolvedClassName)) {
            $this->singletons->setInstance($resolvedClassName, $instance);
            $this->config->setSingletonInstantiated($resolvedClassName);
        }

        // this dependency is resolved and must be removed
        unset($this->resolving[$id]);

        return $instance;
    }


    protected function callDecorators(string $className, $instance, array $path):void
    {
        $decorators = $this->config->getClassDecorators($className);
        foreach ($decorators as $decorator) {
            $this->invokeRecursive($decorator, [
                $className => $instance
            ], $path);
        }
    }


    protected function createInstantiationArguments(string $className, array $params = null):ArgumentList
    {
        $list = new ArgumentList($this->reflector->getConstructorParametersInfo($className));
        $list->addConfig($this->config->getClassParameters($className));
        $list->addConfig($this->config->parseClassParameters($className, $params));
        $missingBuiltIns = $list->getMissing(ArgumentList::TYPE_UNTYPED | ArgumentList::TYPE_BUILTIN);
        if (!empty($missingBuiltIns)) {
            throw ArgumentListException::missingValues($missingBuiltIns);
        }
        return $list;
    }



    protected function resolveDependencies(ArgumentList $list, array $path):void
    {
        foreach ($list->getMissing(ArgumentList::TYPE_CLASS) as $name) {
            $class = $list->getType($name);
            $instance = $this->instantiateRecursive($class, null, $path);
            $list->setValue($name, $instance);
        }
    }



}