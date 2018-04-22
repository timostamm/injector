<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 22.04.18
 * Time: 18:37
 */

namespace TS\DependencyInjection\Injector;


use TS\DependencyInjection\Reflection\ParametersInfo;
use TS\DependencyInjection\Reflection\Reflector;

class ArgumentList
{

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
        for ($i = 0; $i < $info->count(); $i++) {
            $this->missingValues[$i] = $info->isRequiredByIndex($i);
        }
    }

    public function count():int
    {
        return $this->length;
    }

    public function values():array
    {
        $values = [];
        for($i = 0; $i < $this->info->count(); $i++) {
            $name = $this->info->findName($i);
            if (array_key_exists($i, $this->providedValues)) {
                $values[$name] = $this->providedValues[$i];
            } else {
                foreach ($this->configs as $config) {
                    /** @var $config ParametersConfig */
                    if ($config->hasValueForIndex($i)) {
                        $values[$name] = $config->getValueForIndex($i);
                        break;
                    }
                }
            }
        }
        return $values;
    }

    public function provideMissingValue(string $name, $value):void
    {
        $index = $this->info->indexOf($name);
        if (empty($index)) {
            throw new \OutOfRangeException(sprintf('%s is out of range.', $name));
        }
        if ($this->missingValues[$index] !== true) {
            throw new \OutOfRangeException(sprintf('%s is not missing.', $name));
        }
        // TODO type checken
        $this->providedValues[$index] = $value;
    }

    public function getType(string $name):?string
    {
        $index = $this->info->indexOf($name);
        if (is_null($index)) {
            throw new \OutOfRangeException(sprintf('%s is out of range.', $name));
        }
        foreach ($this->configs as $config) {
            /** @var $config ParametersConfig */
            if ($config->hasHintForIndex($index)) {
                return $config->getHintForIndex($index);
            }
        }
        return $this->info->getTypeByIndex($index);
    }

    // TODO getOptionalValues

    public function getMissing():array
    {
        $names = [];
        for($i = 0; $i < $this->info->count(); $i++) {
            if ($this->missingValues[$i]) {
                $names[] = $this->info->findName($i);
            }
        }
        return $names;
    }

    public function getMissingDependencies():array
    {
        $names = [];
        foreach ($this->getMissing() as $name) {
            $type = $this->getType($name);
            if (! Reflector::isBuiltinType($type)) {
                $names[] = $name;
            }
        }
        return $names;
    }

    public function getMissingBuiltins():array
    {
        $names = [];
        foreach ($this->getMissing() as $name) {
            $type = $this->getType($name);
            if (is_null($type) || Reflector::isBuiltinType($type)) {
                $names[] = $name;
            }
        }
        return $names;
    }



    public function addConfig(ParametersConfig $config):void
    {
        $this->length = max($this->length, $config->getMaxValueIndex());

        array_unshift($this->configs, $config);

        for ($i = 0; $i < $config->getMaxValueIndex(); $i++) {
            if ($config->hasValueForIndex($i)) {
                $this->missingValues[$i] = false;
            }
        }

    }

}