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
            throw new \OutOfRangeException(sprintf('Parameter %s is undefined.', $name));
        }
        return $this->values[$name];
    }


    public function hasType(string $name):bool
    {
        return array_key_exists($name, $this->hints);
    }

    public function getType(string $name):string
    {
        if (! $this->hasType($name)) {
            throw new \OutOfRangeException(sprintf('Parameter %s is undefined.', $name));
        }
        return $this->hints[$name];
    }



    public function isEmpty():bool
    {
        return $this->empty;
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
            if ($this->hasType($name) && $this->hasValue($name)) {
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
        $by = sprintf('\'hint $%s\' => %s%s', $name, $hint, Reflector::isBuiltinType($hint)?'':'::class');
        if (! $this->info->includes($name)) {
            throw ParameterConfigException::hintParameterNotFound($by);
        }
        $this->parseTypeHint($name, $hint, $by);
    }

    protected function parseTypeHintForIndex(int $index, string $hint ):void
    {
        $name = $this->info->findName($index);
        $by = sprintf('\'hint #%s\' => %s%s', $name, $hint, Reflector::isBuiltinType($hint)?'':'::class');
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
            throw ParameterConfigException::redundantHint($name, $hint);
        }

        if (! $this->info->isTypeAssignable($hint, $name)) {
            throw ParameterConfigException::hintNotAssignable($name, $hint, $this->info->getType($name));
        }
        $this->setHint($name, $hint, $by);
    }


    protected function parseValuesArray(array $values):void
    {
        if (! $this->info->hasVariadic() && count($values) > $this->info->count()) {
            throw ParameterConfigException::tooManyParameters($this->info->count(), count($values));
        }
        foreach ($this->info->getNames() as $index => $name) {
            if ( $this->info->isVariadic($name) ) {
                break;
            }
            if ( $index >= count($values) ) {
                break;
            }
            $this->setValue($name, $values[$index], 'argument value array');
        }
        if ($this->info->hasVariadic()) {
            $restName = $this->info->getVariadic();
            $offset = $this->info->indexOf($restName);
            $rest = array_slice($values, $offset);
            $this->setValue($restName, $rest, 'argument value array');
        }
    }


    protected function parseValueForIndex(int $index, $value ):void
    {
        $name = $this->info->findName($index);
        if (! $name) {
            throw ParameterConfigException::parameterNotFound($index);
        }
        $by = sprintf('#%s', $index);
        $this->setValue($name, $value, $by);
    }


    protected function parseValueForName( string $name, $value ):void
    {
        if (! $this->info->includes($name)) {
            throw ParameterConfigException::parameterNameNotFound($name);
        }
        $by = sprintf('$%s', $name, Reflector::labelForValue($value));
        $this->setValue($name, $value, $by);
    }


    protected function parseValueForSpread( string $name, $value ):void
    {
        if (! $this->info->includes($name)) {
            throw ParameterConfigException::parameterNameNotFound($name);
        }
        if (! $this->info->isVariadic($name)) {
            throw ParameterConfigException::spreadParamNotVariadic($name);
        }
        if (! is_iterable($value)) {
            throw ParameterConfigException::spreadValueNotIterable($name, $value);
        }
        $arr = [];
        foreach ($value as $append) {
            $arr[] = $append;
        }
        $by = sprintf('...$%s', $name);
        $this->setValue($name, $arr, $by);
    }


    protected function setValue(string $name, $value, string $by):void
    {
        if ( isset($this->valuesBy[$name]) ) {
            throw ParameterConfigException::duplicateParameter($by, $this->valuesBy[$name]);
        }
        if (! $this->info->includes($name)) {
            throw ParameterConfigException::parameterNameNotFound($name);
        }
        if ( ! $this->info->isValueAssignable($value, $name)) {
            throw ParameterConfigException::valueNotAssignable($name, $value, $this->info);
        }

        $this->valuesBy[$name] = $by;
        $this->values[$name] = $value;
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

            if ($this->hasValue($name)) {
                $val = Reflector::labelForValue($this->getValue($name));
                if ($this->info->isVariadic($name)) {
                    $a[] = sprintf('...$%s = %s', $name, $val);
                } else {
                    $a[] = sprintf('$%s = %s', $name, $val);
                }

            } else if ($this->hasType($name)) {
                $hint = $this->getType($name);
                if (Reflector::isBuiltinType($hint)) {
                    $a[] = sprintf('hint $%s as %s', $name, $this->getType($name));
                } else {
                    $a[] = sprintf('hint $%s as %s::class', $name, $this->getType($name));
                }

            } else if ($this->info->isRequired($name)) {

                $a[] = sprintf('$%s = ?', $name);

            }

        }
        return sprintf('ParametersConfig(%s)', join(', ', $a));
    }




}