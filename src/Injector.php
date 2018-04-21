<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 18.04.18
 * Time: 17:44
 */

namespace TS\DependencyInjection;

use Closure;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use TS\DependencyInjection\Exception\ConfigurationException;
use TS\DependencyInjection\Exception\InjectionException;
use TS\DependencyInjection\Injector\SingletonManager;
use TS\DependencyInjection\Reflection\Reflector;




// TODO switch to new params and implement default params.


// TODO Doku params:

// TODO named argument value [ '$a' => 123 ]
// TODO named argument value [ '$rest' => [1,2,3] ]
// TODO named argument value [ '...$rest' => [1,2,3] ]

// TODO indexed argument values [ 'abc', 123 ]
// TODO indexed argument value [ '#1' => 123 ]

// TODO class alias  [ 'MyInterface' => 'MyClass' ]

// TODO class instance  [ 'MyInterface' => $myInstance ]

// TODO type hint [ 'hint $pdo' => 'MyClass' ]
// TODO type hint [ 'hint #0' => 'MyClass' ]


// TODO blacklist/whitelist wie Symfony container? https://symfony.com/doc/current/service_container.html#service-psr4-loader
// TODO compile():InjectorInterface ?



class Injector implements InjectorInterface
{


    protected $strict_types;
    protected $current_stack = null;
    protected $aliases;
    protected $singletons;
    protected $default_params;
    protected $reflector;

    public function __construct(bool $strict_types = false)
    {
        $this->reflector = new Reflector();
        $this->strict_types = $strict_types;
        $this->aliases = [];
        $this->singletons = new SingletonManager();
        $this->default_params = [];
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
    public function alias(string $from, string $to, array $params = null):void
    {
        $this->aliases[$from] = $to;
        if (! empty($params)) {
            $this->defaults($to, $params);
        }
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
    public function singleton(string $classname, array $params = null):void
    {
        $this->singletons->register($classname, $params);
    }


    // TODO reconsider name: params() ?
    /**
     *
     * Set default parameters for a class.
     *
     * @param string $classname
     * @param array $params
     */
    public function defaults(string $classname, array $params):void
    {
        $this->default_params[$classname] = $params;
    }



    // TODO
    /**
     * Decorate a class.
     *
     * The injector creates the instance, but you can manipulate
     * or wrap it.
     *
     * $inj->decorate(Foo::class, function(Foo $foo, InjectorInterface $i):Foo {
     *    $foo->setLogger( $i->instanciate(LoggerInterface::class) );
     *    return $foo;
     * })
     *
     */
    public function decorate(string $classname, callable $decorate ):void
    {
    }


    // TODO
    /**
     * Intercept the instantiation of a class.
     *
     * The injector passes control over to you.
     *
     * $inj->intercept(Foo::class, function(array $params, InjectorInterface $i):Foo {
     *   return $i->instanciate(FooBar::class, $params);
     * })
     *
     */
    public function intercept(string $classname, callable $intercept):void
    {}


    /**
     * Call a function or object method and automatically inject
     * dependencies.
     *
     * The injector reads the type hints of all arguments and
     * recursively creates new instances for the dependencies.
     *
     * function foo
     *
     * @param callable $callable
     * @param array|null $params
     * @return mixed
     */
    public function invoke(callable $callable, array $params = null)
    {
        $function = $this->reflector->getCallable($callable);




        // TODO cleanup circular dependency check

        if (! $this->current_stack) {
            $this->current_stack = [
                'ids' => [],
                'path' => []
            ];
        }
        if ($function instanceof ReflectionMethod) {
            $id = sprintf('%s::%s', $function->getDeclaringClass()->getName(), $function->getName());
        } else {
            $id = sprintf('%s', $function->getName());
        }
        if (isset($this->current_stack['ids'][ $id ])) {
            throw new \LogicException("circ ref detected");
        }
        $this->current_stack['ids'][ $id ] = [
            'callable' => $callable,
            'reflection' => $function,
            'params' => $params
        ];
        $this->current_stack['path'][] = sprintf('Injector::call(< %s() >)', $id);





        // TODO

        $parameters = $this->reflector->getCallableParameters($callable);

        $args = $this->resolveParameters($parameters, $params ?: []);

        if ($function instanceof ReflectionFunction) {
            return $function->invokeArgs($args);
        } else if ($callable instanceof Closure) {
            return $function->invokeArgs($callable, $args);
        } else {
            return $function->invokeArgs($callable[0], $args);
        }



        $this->current_stack = null;

    }



    public function instantiate(string $classname, array $params = null)
    {

        if (array_key_exists($classname, $this->aliases)) {
            $classname = $this->aliases[$classname];
        }


        if (! $this->reflector->classExists($classname) ) {
            throw InjectionException::classNotFound($classname);
        }


        if (! $this->current_stack) {
            $this->current_stack = [
                'ids' => [],
                'path' => []
            ];
        }
        $id = sprintf('%s::__construct()', $classname);

        $this->current_stack['path'][] = sprintf('Injector::instanciate( %s )', $id);

        if (isset($this->current_stack['ids'][ $id ])) {

            $msg = 'CIRCULAR REFERENCE DETECTED.';
            var_dump($this->current_stack);
            throw new \LogicException($msg);
        }
        $this->current_stack['ids'][ $id ] = [
            'classname' => $classname,
            'reflection' => $class = $this->reflector->getClass($classname),
            'params' => $params
        ];

        if ($this->singletons->isRegistered($classname) && ! is_null($params)) {
            throw ConfigurationException::cannotUseParametersForSingleton($classname);
        }

        if ($this->singletons->hasInstance($classname)) {

            $instance = $this->singletons->getInstance($classname);

        } else {

            if (! $this->reflector->isClassInstantiable($classname)) {
                throw InjectionException::classNotInstantiable($classname);
            }

            // determine actual params
            if ($this->singletons->isRegistered($classname)) {
                $actual_params = $this->singletons->getParameters($classname);
            } else if (array_key_exists($classname, $this->default_params)) {
                $actual_params = array_replace([], $this->default_params[$classname], $params ?: []);
            } else {
                $actual_params = $params ?: [];
            }


            $parameters = $this->reflector->getConstructorParameters($classname);
            $args = $this->resolveParameters($parameters, $actual_params);
            $instance = $this->reflector->instantiateClass($classname, $args);

            $this->singletons->setInstanceIfApplicable($classname, $instance);

        }

        $this->current_stack = null;
        return $instance;
    }


    // TODO switch to ParametersConfig
    protected function resolveParameters(array $parameters, array $param_config): array
    {

/*
        $default_config = new ParameterConfig(new ParameterInfos($parameters));
        $default_config->parse($param_config);


        $local_config = clone $default_config;
        $local_config->parse($param_config);




        $arguments = new Arguments( new ParameterInfos($parameters) );
        foreach ($local_config->getValuesByIndex() as $index => $value) {
            $arguments->set($index, $value);
        }



        $arguments = new Arguments( $local_config );
        $arguments->areSatisfied();
        $arguments->getUntyped();
        $arguments->getUntyped();

        $arguments->toArray();


        $config = new ParameterConfig($param_config);

        $arguments = new Arguments( new ParameterInfos($parameters) );

        foreach ($arguments->getRequiredNames() as $index => $name) {
            if ( $config->hasValueForIndex($index) ) {
                $arguments->set($index, $config->getValueForIndex());
            }
            if ( $config->hasValueForName($name) ) {
                $arguments->set($index, $config->getValueForName($name));
            }

        }
*/


        $values = [];

        foreach ($parameters as $i => $param) {

            $name = $param->getName();

            if ($param->isVariadic()) {

                /*
                if ($config->hasValueForName($name)) {

                    $iterable = is_iterable($config->getValueForName($name)) ? $config->getValueForName($name) : [$config->getValueForName($name)];
                    foreach ($iterable as $append) {
                        $values[] = $append;
                    }

                } else if ($config->hasValueForIndex($i)) {
                    $j = $i;
                    while($config->hasValueForIndex($j)) {
                        $values[] = $config->getValueForIndex($j);
                        $j++;
                    }
                }
*/

                if (array_key_exists('$' . $name, $param_config)) {
                    $iterable = $this->convertToParameterType($param_config['$'.$name], $param);
                    foreach ($iterable as $append) {
                        $values[] = $append;
                    }
                }

            } else if (!$param->hasType() || $param->getType()->isBuiltin()) {

                // does the param config contain a value for our parameter name?
                if (array_key_exists('$' . $name, $param_config)) {
                    $values[] = $this->convertToParameterType($param_config['$'.$name], $param);
                    continue;
                }

                if ($param->isOptional()) {
                    continue;
                }

                if ($param->isDefaultValueAvailable()) {
                    $values[] = $param->getDefaultValue();
                    continue;
                }

                throw LegacyInjectorException::missingParameter($param);

            } else {

                $type = strval($param->getType());

                // does the param config contain a value for our parameter name?
                if (array_key_exists('$' . $name, $param_config)) {
                    $values[] = $this->convertToParameterType($param_config['$'.$name], $param);
                    continue;
                }

                // does the param config contain a value for our parameter type?
                if (array_key_exists($type, $param_config)) {
                    if ( is_string($param_config[$type]) ) {
                        // it is a string, it should be a class
                        $val = $this->instantiate($param_config[$type]);
                    } else {
                        // it should be an instance of our parameter type
                        $val = $param_config[$type];
                    }
                    $values[] = $this->convertToParameterType($val, $param);
                    continue;
                }


                /*
                $val = $this->instantiate($type);
                $values[] = $this->convertToParameterType($val, $param);

                if ($this->container && $this->container->has($type)) {
                    $values[] = $this->convertToParameterType($this->container->get($type), $param);
                    continue;
                }
                */


                if ($param->isOptional()) {
                    continue;
                }

                if ($param->isDefaultValueAvailable()) {
                    $values[] = $param->getDefaultValue();
                    continue;
                }

                if ($param->allowsNull()) {
                    $values[] = null;
                    continue;
                }


                $val = $this->instantiate($type);
                $values[] = $this->convertToParameterType($val, $param);


                ///throw InjectorException::missingParameter($param);

            }

        }

        return $values;
    }


    // TODO replace with ParametersConfig
    // TODO instead of complex conversion, simply use settype()?
    // TODO extra parameters must throw exceptions.
    // TODO must be possible to inspect unresolvable values from the outside -> reflector?
    protected function convertToParameterType($value, ReflectionParameter $param)
    {
        if ( $param->allowsNull() === false && is_null($value) ) {
            throw LegacyInjectorException::wrongParameterType($value, $param);
        }

        if ( $param->isVariadic() ) {

            return is_iterable($value) ? $value : [$value];
        }

        if ($param->hasType() === false) {

            return $value;

        } else if ($param->getType()->isBuiltin()) {

            $type = strval($param->getType());

            if ($type === 'int') {
                if (! is_numeric($value)) {
                    throw LegacyInjectorException::wrongParameterType($value, $param);
                }
                if ($this->strict_types && ! is_integer($value)) {
                    throw LegacyInjectorException::wrongParameterType($value, $param);
                }
                return intval($value);

            } else if ($type === 'float') {
                if (! is_numeric($value)) {
                    throw LegacyInjectorException::wrongParameterType($value, $param);
                }
                if ($this->strict_types && ! is_float($value)) {
                    throw LegacyInjectorException::wrongParameterType($value, $param);
                }
                return floatval($value);

            } else if ($type === 'string') {
                if (! is_scalar($value)) {
                    throw LegacyInjectorException::wrongParameterType($value, $param);
                }
                if ($this->strict_types && ! is_string($value)) {
                    throw LegacyInjectorException::wrongParameterType($value, $param);
                }
                return strval($value);

            } else if ($type === 'bool') {
                if (! is_scalar($value)) {
                    throw LegacyInjectorException::wrongParameterType($value, $param);
                }
                if ($this->strict_types && ! is_bool($value)) {
                    throw LegacyInjectorException::wrongParameterType($value, $param);
                }
                return boolval($value);

            } else if ($type === 'object') {
                if ( ! is_object($value) ) {
                    throw LegacyInjectorException::wrongParameterType($value, $param);
                }

            } else if ($type === 'callable') {
                if ( ! is_callable($value) ) {
                    throw LegacyInjectorException::wrongParameterType($value, $param);
                }

            } else if ($type === 'resource') {
                if ( ! is_resource($value) ) {
                    throw LegacyInjectorException::wrongParameterType($value, $param);
                }

            } else if ($type === 'array') {
                if ( ! is_array($value) ) {
                    throw LegacyInjectorException::wrongParameterType($value, $param);
                }

            } else if ($type === 'iterable') {
                if ( ! is_iterable($value) ) {
                    throw LegacyInjectorException::wrongParameterType($value, $param);
                }
            }

        } else {

            $class = strval($param->getType());

            if (! is_a($value, $class) ) {
                throw LegacyInjectorException::wrongParameterType($value, $param);
            }

        }

        return $value;
    }

}