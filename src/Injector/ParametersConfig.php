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

class ParametersConfig
{


    const RE_INDEX = '/^#([0-9]+)$/';
    const RE_NAME = '/^\\$([a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*)$/';
    const RE_SPREAD = '/^\.\.\.\\$([a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*)$/';
    const RE_HINT_INDEX = '/^hint #([0-9]+)$/';
    const RE_HINT_NAME = '/^hint \\$([a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*)$/';
    const RE_CLASS = '/^(?:[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*\\\\)*[a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*$/';

    protected $infos;
    protected $values;
    protected $valuesBy;
    protected $hints;
    protected $hintsBy;
    protected $empty = true;



    public function __construct(ParametersInfo $infos)
    {
        $this->infos = $infos;
        $this->values = [];
        $this->valuesBy = [];
        $this->hints = [];
        $this->hintsBy = [];
    }


    public function hasValueForIndex(int $index):bool
    {
        return array_key_exists($index, $this->values);
    }

    public function getValueForIndex(int $index)
    {
        if (! $this->hasValueForIndex($index)) {
            throw new \OutOfRangeException(sprintf('%s is out of range.', $index));
        }
        return $this->values[$index];
    }

    public function hasHintForIndex(int $index):bool
    {
        return array_key_exists($index, $this->hints);
    }

    public function getHintForIndex(int $index)
    {
        if (! $this->hasHintForIndex($index)) {
            throw new \OutOfRangeException(sprintf('%s is out of range.', $index));
        }
        return $this->hints[$index];
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

                } else if (preg_match(self::RE_HINT_NAME, $key, $matches)) {

                    $this->parseTypeHintForName($matches[1], $value);

                } else if (preg_match(self::RE_HINT_INDEX, $key, $matches)) {

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
        for ($i = 0; $i < count($this->values); $i++) {
            if ($this->hasHintForIndex($i) && $this->hasValueForIndex($i)) {
                $hintBy = $this->hintsBy[$i];
                $valueBy = $this->valuesBy[$i];
                $value = $this->getValueForIndex($i);
                throw ParameterConfigException::hintAndValueCollision($hintBy, $valueBy, $value, $this->infos->findName($i) ?? $i);
            }
        }
    }

    protected function parseValueForClass(string $class, $value):void
    {
        $found = false;
        for($i = 0; $i < $this->infos->count(); $i++) {
            $t = $this->infos->getType($i);
            if ( $t === $class ) {
                $by = sprintf('%s::class => %s', $class, Reflector::labelForValue($value));
                $this->setValue($i, $value, $by);
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
        for($i = 0; $i < $this->infos->count(); $i++) {
            $t = $this->infos->getType($i);
            if ( $t === $from ) {
                $this->setHint($i, $to, $from . '::class => ' . $to . '::class');
                $found = true;
            }
        }
        if (! $found) {
            throw ParameterConfigException::aliasParameterNotFound($from, $to);
        }
    }


    protected function parseTypeHintForName(string $name, $hint ):void
    {
        $index = $this->infos->findIndex($name);
        if (is_null($index)) {
            throw ParameterConfigException::parameterNotFound($name, true);
        }
        $by = sprintf('\'hint $%s\' => %s::class', $name, $hint);
        $this->parseTypeHint($index, $name, $hint, $by);
    }

    protected function parseTypeHintForIndex(int $index, $hint ):void
    {
        $oor = $index < 0 || $index >= $this->infos->count();
        if ($oor && $this->infos->isVariadic() == false ) {
            throw ParameterConfigException::parameterNotFound($index, true);
        }

        $by = sprintf('\'hint #%s\' => %s::class', $index, $hint);
        $this->parseTypeHint($index, null, $hint, $by);
    }


    protected function parseTypeHint(int $index, string $name=null, $hint, string $by):void
    {
        $key = $name ?? $index;

        if (!is_string($hint)) {
            throw ParameterConfigException::hintInvalid($key, $hint);
        }
        if ($this->infos->isVariadic($index)) {
            throw ParameterConfigException::cannotHintVariadic($key, $hint);
        }

        $type = $this->infos->getType($index);

        if (! $type) {
            $by = sprintf('$%s as %s', $key, $hint);
            $this->setHint($index, $hint, $by);
            return;
        }

        if ($type === $hint) {
            throw ParameterConfigException::redundantHint($key, $hint, $type);
        }
        if (Reflector::isBuiltinType($type) || Reflector::isBuiltinType($hint)) {
            throw ParameterConfigException::alreadyHinted($key, $hint, $type);
        }
        if (! is_subclass_of($hint, $type, true)) {
            throw ParameterConfigException::alreadyHinted($key, $hint, $type);
        }

        $this->setHint($index, $hint, $by);
    }


    protected function parseValuesArray(array $values):void
    {
        if ($this->infos->isVariadic() == false && count($values) > $this->infos->count()) {
            throw ParameterConfigException::tooManyParameters($this->infos->count(), count($values));
        }
        foreach ($values as $index => $value) {
            $this->setValue($index, $value);
        }
    }


    protected function parseValueForIndex(int $index, $value ):void
    {
        $oor = $index < 0 || $index >= $this->infos->count();
        if ($oor && $this->infos->isVariadic() == false ) {
            throw ParameterConfigException::parameterNotFound($index);
        }
        $by = sprintf('#%s', $index);
        $this->setValue($index, $value, $by);
    }


    protected function parseValueForName( string $name, $value ):void
    {
        $index = $this->infos->findIndex($name);
        if (is_null($index)) {
            throw ParameterConfigException::parameterNotFound($name);
        }
        $by = sprintf('$%s', $name, Reflector::labelForValue($value));
        $this->setValue($index, $value, $by);
    }


    protected function parseValueForSpread( string $name, $value ):void
    {
        $index = $this->infos->findIndex($name);
        if (is_null($index)) {
            throw ParameterConfigException::parameterNotFound($name);
        }
        if (! $this->infos->isVariadic($index) ) {
            throw ParameterConfigException::cannotSpreadNonVariadic($name);
        }
        if (! is_iterable($value)) {
            throw ParameterConfigException::spreadValueNotIterable($name, $value);
        }
        $i = $index;
        foreach ($value as $item) {
            $by = sprintf('...$%s', $name);
            $this->setValue($i++, $item, $by);
        }
    }


    protected function setValue(int $index, $value, string $by=null):void
    {
        if (! empty($by)) {
            if ( isset($this->valuesBy[$index]) ) {
                throw ParameterConfigException::duplicateParameter($by, $this->valuesBy[$index]);
            }
            $this->valuesBy[$index] = $by;
        }
        $this->values[$index] = $value;
    }

    protected function setHint(int $index, $type, string $by=null):void
    {
        if (! empty($by)) {
            if ( isset($this->hintsBy[$index]) ) {
                throw ParameterConfigException::duplicateHint($by, $this->hintsBy[$index], $this->infos->findName($index));
            }
            $this->hintsBy[$index] = $by;
        }
        $this->hints[$index] = $type;
    }



    public function __toString()
    {
        $a = [];
        foreach ($this->infos->getNames() as $index => $name) {
            $required = $this->infos->isRequired($index);
            if ($this->hasValueForIndex($index)) {
                $value = $this->getValueForIndex($index);
                $a[] = sprintf('\'$%s\' => %s', $name, Reflector::labelForValue($value));
            } else if ($this->hasHintForIndex($index) && $required) {
                $hint = $this->getHintForIndex($index);
                $a[] = sprintf('\'$%s\' => %s', $name, $hint);
            }
        }
        return sprintf('ParameterConfig(%s)', join(', ', $a));
    }




}