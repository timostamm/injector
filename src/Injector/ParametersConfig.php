<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 11:34
 */

namespace TS\DependencyInjection\Injector;


use TS\DependencyInjection\Exception\ParameterConfigException;
use TS\DependencyInjection\Reflection\ParametersInfo;
use TS\DependencyInjection\Reflection\Reflector;


// TODO alias sollte nicht als hint, sondern separat gespeichert sein
//

// TODO rest-parameter nur ein wert!



class ParametersConfig
{


    const RE_INDEX = '/^#([0-9]+)$/';
    const RE_NAME = '/^\\$([a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*)$/';
    const RE_SPREAD = '/^\.\.\.\\$([a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*)$/';
    const RE_HINT_INDEX = '/^hint #([0-9]+)$/';
    const RE_HINT_NAME = '/^hint \\$([a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*)$/';
    const RE_CLASS = '/^(?:[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*\\\\)*[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*$/';

    protected $info;
    protected $values;
    protected $valuesBy;
    protected $valueMax;
    protected $hints;
    protected $hintsBy;
    protected $empty = true;



    public function __construct(ParametersInfo $info)
    {
        $this->info = $info;
        $this->values = [];
        $this->valuesBy = [];
        $this->valueMax = 0;
        $this->hints = [];
        $this->hintsBy = [];
    }


    public function hasValue(string $name):bool
    {
        return array_key_exists($name, $this->values);
    }
    public function getValue(string $name)
    {
        if (! $this->hasValue($name)) {
            throw new \OutOfRangeException(sprintf('%s is undefined.', $name));
        }
        return $this->values[$name];
    }



    /** @deprecated  */
    public function hasValueForIndex(int $index):bool
    {
        return array_key_exists($index, $this->values);
    }

    /** @deprecated  */
    public function getValueForIndex(int $index)
    {
        if (! $this->hasValueForIndex($index)) {
            throw new \OutOfRangeException(sprintf('%s is out of range.', $index));
        }
        return $this->values[$index];
    }




    public function hasHint(string $name):bool
    {
        return array_key_exists($name, $this->hints);
    }

    public function getHint(string $name):string
    {
        if (! $this->hasHint($name)) {
            throw new \OutOfRangeException(sprintf('%s is out of range.', $name));
        }
        return $this->hints[$name];
    }




    public function isEmpty():bool
    {
        return $this->empty;
    }

    public function getMaxValueIndex():int
    {
        return $this->valueMax;
    }


    public function parse(array $config) {

        if (empty($config)) {

        } else if (array_keys($config) === range(0, count($config) - 1)) {

            $this->parseValuesArray($config);
            $this->empty = false;

        } else {

            foreach ($config as $key => $value) {
                $this->empty = false;

                if (is_int($key)) {

                    throw ParameterConfigException::parameterKeyInvalid($key, $value);

                } else if (preg_match(self::RE_NAME, $key, $matches)) {

                    $this->parseValueForName($matches[1], $value);

                } else if (preg_match(self::RE_INDEX, $key, $matches)) {

                    $this->parseValueForIndex((int)$matches[1], $value);

                } else if (preg_match(self::RE_HINT_NAME, $key, $matches) && is_string($value)) {

                    $this->parseTypeHintForName($matches[1], $value);

                } else if (preg_match(self::RE_HINT_INDEX, $key, $matches) && is_string($value)) {

                    $this->parseTypeHintForIndex($matches[1], $value);

                } else if (preg_match(self::RE_SPREAD, $key, $matches)) {

                    $this->parseValueForSpread($matches[1], $value);

                } else if ( (is_string($value) || is_object($value)) && preg_match(self::RE_CLASS, $key, $matches)) {

                    if (is_string($value)) {
                        $this->parseAliasForClass($key, $value);
                    } else {
                        $this->parseValueForClass($key, $value);
                    }

                } else {

                    throw ParameterConfigException::parameterKeyInvalid($key, $value);

                }

            }

            $this->validateHintAndValueCollisions();
        }

        // $this->valuesBy = null;
        // $this->hintsBy = null;
    }

    protected function validateHintAndValueCollisions():void
    {
        foreach ($this->info->getNames() as $name) {
            if ($this->hasHint($name) && $this->hasValue($name)) {
                $hintBy = $this->hintsBy[$name];
                $valueBy = $this->valuesBy[$name];
                $value = $this->getValue($name);
                throw ParameterConfigException::hintAndValueCollision($hintBy, $valueBy, $value, $name);
            }
        }
    }

    protected function parseValueForClass(string $class, $value):void
    {
        $found = false;
        foreach ($this->info->getNames() as $name) {
            $type = $this->info->getType($name);
            if ( $type === $class ) {
                $by = sprintf('%s::class => %s', $class, Reflector::labelForValue($value));
                $this->setValue($name, $value, $by);
                $found = true;
            }
        }
        if (! $found) {
            throw ParameterConfigException::instanceParameterNotFound($class, $value);
        }
    }

    protected function parseAliasForClass(string $from, string $to):void
    {
        $found = false;
        foreach ($this->info->getNames() as $name) {
            $type = $this->info->getType($name);
            if ( $type === $from ) {
                $by = sprintf('%s::class => %s::class', $from, $to);
                $this->setHint($name, $to, $by);
                $found = true;
            }
        }
        if (! $found) {
            throw ParameterConfigException::aliasParameterNotFound($from, $to);
        }
    }


    protected function parseTypeHintForName(string $name, string $hint ):void
    {
        $by = sprintf('\'hint $%s\' as %s::class', $name, $hint);
        if (! $this->info->includes($name)) {
            throw ParameterConfigException::hintParameterNotFound($by);
        }
        $this->parseTypeHint($name, $hint, $by);
    }

    protected function parseTypeHintForIndex(int $index, string $hint ):void
    {
        $name = $this->info->findName($index);
        $by = sprintf('\'hint #%s\' as %s::class', $index, $hint);
        if (! $name) {
            throw ParameterConfigException::hintParameterNotFound($by);
        }
        $this->parseTypeHint($name, $hint, $by);
    }


    protected function parseTypeHint(string $name, string $hint, string $by):void
    {
        if ($this->info->isVariadic($name)) {
            throw ParameterConfigException::cannotHintVariadic($name);
        }

        if ($this->info->getType($name) === $hint) {
            throw ParameterConfigException::redundantHint($by);
        }

        if (! $this->info->isTypeAssignable($hint, $name)) {
            throw ParameterConfigException::hintNotAssignable($by, $this->info->getType($name));
        }
        $this->setHint($name, $hint, $by);



        // TODO cleanup
        return;


        $type = $this->info->getType($name);
        if (! $type) {
            $this->setHint($name, $hint, $by);
            return;
        }

        if ($type === $hint) {
            throw ParameterConfigException::redundantHint($by);
        }

        if (Reflector::isBuiltinType($type) || Reflector::isBuiltinType($hint)) {
            throw ParameterConfigException::hintNotAssignable($by, $type);
        }
        if (! is_subclass_of($hint, $type, true)) {
            throw ParameterConfigException::hintNotAssignable($by, $type);
        }
        $this->setHint($name, $hint, $by);
    }


    protected function parseValuesArray(array $values):void
    {
        if ($this->info->isVariadicByIndex() == false && count($values) > $this->info->count()) {
            throw ParameterConfigException::tooManyParameters($this->info->count(), count($values));
        }
        foreach ($values as $index => $value) {
            $this->setValueByIndex($index, $value);
        }
    }


    protected function parseValueForIndex(int $index, $value ):void
    {
        $name = $this->info->findName($index);
        if (! $name) {
            throw ParameterConfigException::parameterNotFound($index);
        }
        $by = sprintf('#%s', $index);
        $this->setValue($$name, $value, $by);
    }


    protected function parseValueForName( string $name, $value ):void
    {
        if (! $this->info->includes($name)) {
            throw ParameterConfigException::parameterNameNotFound($name, $this->info);
        }
        $by = sprintf('$%s', $name, Reflector::labelForValue($value));
        $this->setValue($name, $value, $by);
    }


    protected function parseValueForSpread( string $name, $value ):void
    {
        $index = $this->info->indexOf($name);
        if (is_null($index)) {
            throw ParameterConfigException::parameterNotFound($name);
        }
        if (! $this->info->isVariadicByIndex($index) ) {
            throw ParameterConfigException::cannotSpreadNonVariadic($name);
        }
        if (! is_iterable($value)) {
            throw ParameterConfigException::spreadValueNotIterable($name, $value);
        }
        $i = $index;
        foreach ($value as $item) {
            $by = sprintf('...$%s', $name);
            $this->setValueByIndex($i++, $item, $by);
        }
    }


    protected function setValue(string $name, $value, string $by=null):void
    {
        if ( isset($this->valuesBy[$name]) ) {
            throw ParameterConfigException::duplicateParameter($by, $this->valuesBy[$name]);
        }
        if (! $this->info->includes($name)) {
            throw ParameterConfigException::parameterNameNotFound($name);
        }
        if ( ! $this->info->isValueAssignable($value, $name)) {
            throw ParameterConfigException::valueNotAssignable($by, $this->info->getType($name), $value);
        }

        $this->valuesBy[$name] = $by;
        $this->values[$name] = $value;
    }


    protected function setValueByIndex(int $index, $value, string $by=null):void
    {
        if (! empty($by)) {
            if ( isset($this->valuesBy[$index]) ) {
                throw ParameterConfigException::duplicateParameter($by, $this->valuesBy[$index]);
            }

            if (! $this->info->isVariadicByIndex($index)) {
                if ( ! $this->info->isValueAssignableByIndex($value, $index)) {
                    throw ParameterConfigException::valueNotAssignable($by, $this->info->getTypeByIndex($index), $value);
                }
            }

            $this->valuesBy[$index] = $by;
        }
        $this->values[$index] = $value;
        $this->valueMax = max($this->valueMax, $index);
    }

    protected function setHint(string $name, $type, string $by=null):void
    {
        if (! empty($by)) {
            if ( isset($this->hintsBy[$name]) ) {
                throw ParameterConfigException::duplicateHint($by, $this->hintsBy[$name], $name);
            }
            $this->hintsBy[$name] = $by;
        }
        $this->hints[$name] = $type;
    }



    public function __toString()
    {
        $a = [];
        foreach ($this->info->getNames() as $index => $name) {
            $required = $this->info->isRequired($name);
            if ($this->hasValue($name)) {
                $value = $this->getValue($name);
                $a[] = sprintf('\'$%s\' => %s', $name, Reflector::labelForValue($value));
            } else if ($this->hasHintForIndex($index) && $required) {
                $hint = $this->getHintForIndex($index);
                $a[] = sprintf('\'$%s\' => %s', $name, $hint);
            }
        }
        return sprintf('ParametersConfig(%s)', join(', ', $a));
    }




}