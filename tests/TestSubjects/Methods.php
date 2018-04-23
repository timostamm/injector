<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 08:40
 */

namespace TS\DependencyInjection\TestSubjects;


class Methods
{

    public function noArguments() {}

    public static function staticNoArguments() {}

    public function int_float_string_bool_array_callable_Arguments(int $int, float $float, string $string, bool $bool, array $array, callable $callable) {}

    public function classArgument(Standalone $standalone, Standalone $optionalStandalone=null) {}

    public function interfaceArgument(StandaloneInterface $standaloneInterface, StandaloneInterface $optionalStandaloneInterface=null) {}

    public function variadicArgument(...$rest) {}

    public function untypedArgument($untyped, $optionalUntyped=null) {}


}