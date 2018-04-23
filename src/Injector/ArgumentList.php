<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 22.04.18
 * Time: 18:37
 */

namespace TS\DependencyInjection\Injector;


use TS\DependencyInjection\Exception\ArgumentListException;
use TS\DependencyInjection\Reflection\ParametersInfo;
use TS\DependencyInjection\Reflection\Reflector;

/**
 * Represents a list of arguments that may be ready to use
 * for a function/method call.
 */
class ArgumentList implements ArgumentInspectionInterface
{

    const TYPE_UNTYPED = 2;
    const TYPE_BUILTIN = 4;
    const TYPE_CLASS = 8;

    protected $info;
    protected $configs;
    protected $missingValues;
    protected $providedValues;
    protected $length;


    public function __construct(ParametersInfo $info)
    {
        $this->info = $info;
        $this->configs = [];
        $this->missingValues = [];
        $this->providedValues = [];
        $this->length = $info->count();
        foreach ($info->getNames() as $name) {
            $this->missingValues[$name] = $info->isRequired($name);
        }
    }


    /**
     * Add a parameter configuration that may provide values
     * and type hints.
     */
    public function addConfig(ParametersConfig $config):void
    {
        array_unshift($this->configs, $config);
        foreach ($this->info->getNames() as $name) {
            if ($config->hasValue($name)) {
                $this->missingValues[$name] = false;
            }
        }
    }


    /**
     * Get the argument values, ready to use for a function/method call.
     *
     * @throws ArgumentListException if some arguments are missing.
     */
    public function values():array
    {
        $values = [];
        foreach ($this->info->getNames() as $name) {

            if ($this->hasExplicitValue($name)) {

                if ($this->info->isVariadic($name)) {
                    foreach ($this->getExplicitValue($name) as $append) {
                        $values[] = $append;
                    }
                } else {
                    $values[] = $this->getExplicitValue($name);
                }

            } else if ($this->info->isRequired($name)) {

                if ($this->info->hasDefaultValue($name)) {
                    $values[] = $this->info->getDefaultValue($name);
                } else if ($this->info->allowsNull($name)) {
                    $values[] = null;
                } else {
                    throw ArgumentListException::missingValues($this->getMissing());
                }

            }

        }
        return $values;
    }


    /**
     * @throws ArgumentListException if the parameter does not exist or the value is not assignable.
     */
    public function setValue(string $name, $value):void
    {
        if (! $this->info->includes($name)) {
            throw ArgumentListException::parameterNotFound($name, $this->info);
        }
        if (! $this->info->isValueAssignable($value, $name)) {
            throw ArgumentListException::valueNotAssignable($name, $value, $this->info);
        }
        $this->missingValues[$name] = false;
        $this->providedValues[$name] = $value;
    }


    /**
     * Get the type of an argument.
     *
     * May be NULL if untyped, a built-in type or a class name.
     *
     */
    public function getType(string $name):?string
    {
        if (! $this->info->includes($name)) {
            throw ArgumentListException::parameterNotFound($name, $this->info);
        }
        foreach ($this->configs as $config) {
            /** @var $config ParametersConfig */
            if ($config->hasType($name)) {
                return $config->getType($name);
            }
        }
        return $this->info->getType($name);
    }


    /**
     * Get the names of optional arguments that are not yet provided.
     */
    public function getOptional(int $type = self::TYPE_BUILTIN | self::TYPE_UNTYPED | self::TYPE_CLASS):array
    {
        $includeUntyped = ($type & self::TYPE_UNTYPED) === self::TYPE_UNTYPED;
        $includeBuiltin = ($type & self::TYPE_BUILTIN) === self::TYPE_BUILTIN;
        $includeClass = ($type & self::TYPE_CLASS) === self::TYPE_CLASS;

        $names = [];
        foreach ($this->info->getNames() as $name) {
            if ($this->info->isRequired($name) ) {
                continue;
            }
            if ($this->hasExplicitValue($name)) {
                continue;
            }
            $type = $this->getType($name);
            if ( ! $includeUntyped && is_null($type) ) {
                continue;
            }
            $isBuiltin = Reflector::isBuiltinType($type);
            if ( ! $includeBuiltin && $isBuiltin && ! is_null($type) ) {
                continue;
            }
            if ( ! $includeClass && ! $isBuiltin && ! is_null($type) ) {
                continue;
            }
            $names[] = $name;
        }
        return $names;
    }


    /**
     * Get the names of missing arguments.
     */
    public function getMissing(int $type = self::TYPE_BUILTIN | self::TYPE_UNTYPED | self::TYPE_CLASS ):array
    {
        $includeUntyped = ($type & self::TYPE_UNTYPED) === self::TYPE_UNTYPED;
        $includeBuiltin = ($type & self::TYPE_BUILTIN) === self::TYPE_BUILTIN;
        $includeClass = ($type & self::TYPE_CLASS) === self::TYPE_CLASS;

        $names = [];
        foreach ($this->info->getNames() as $name) {
            if (! $this->missingValues[$name]) {
                continue;
            }
            $type = $this->getType($name);
            if ( ! $includeUntyped && is_null($type) ) {
                continue;
            }
            $isBuiltin = Reflector::isBuiltinType($type);
            if ( ! $includeBuiltin && $isBuiltin && ! is_null($type) ) {
                continue;
            }
            if ( ! $includeClass && ! $isBuiltin && ! is_null($type) ) {
                continue;
            }
            $names[] = $name;
        }
        return $names;
    }


    protected function hasExplicitValue(string $name):bool
    {
        if (array_key_exists($name, $this->providedValues)) {
            return true;
        } else {
            foreach ($this->configs as $config) {
                /** @var $config ParametersConfig */
                if ($config->hasValue($name)) {
                    return true;
                }
            }
        }
        return false;
    }

    protected function getExplicitValue(string $name)
    {
        if (array_key_exists($name, $this->providedValues)) {
            return $this->providedValues[$name];
        } else {
            foreach ($this->configs as $config) {
                /** @var $config ParametersConfig */
                if ($config->hasValue($name)) {
                    return $config->getValue($name);
                }
            }
            throw ArgumentListException::missingValues($this->getMissing());
        }
    }



}