<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 10:53
 */

namespace TS\DependencyInjection;


use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use TS\DependencyInjection\Exception\ParameterConfigException;
use TS\DependencyInjection\Injector\ParametersConfig;
use TS\DependencyInjection\Reflection\ParametersInfo;
use TS\DependencyInjection\TestSubjects\Methods;
use TS\DependencyInjection\TestSubjects\Standalone;
use TS\DependencyInjection\TestSubjects\StandaloneInterface;


class ParametersConfigTest extends TestCase
{

    /**
     * @var ParametersConfig
     */
    protected $builtins;
    /**
     * @var ParametersConfig
     */
    protected $class;
    /**
     * @var ParametersConfig
     */
    protected $interface;
    /**
     * @var ParametersConfig
     */
    protected $rest;


    public function testParamForAliasInstanceNotFound()
    {
        $this->expectException(ParameterConfigException::class);
        $msg = sprintf('Cannot set instances for %s::class => object(%s). No matching parameter found.', ParametersConfigTest::class, ParametersConfigTest::class);
        $this->expectExceptionMessage($msg);
        $this->interface->parse([ParametersConfigTest::class => $this]);
    }

    public function testAliasInstance()
    {
        $e = new Standalone();
        $this->interface->parse([StandaloneInterface::class => $e]);
        $a = $this->interface->getValueForIndex(0);
        $this->assertSame($e, $a);
    }

    public function testAlias()
    {
        $this->interface->parse([
            StandaloneInterface::class => Standalone::class
        ]);
        $a = $this->interface->getHintForIndex(0);
        $this->assertSame(Standalone::class, $a);
        $a = $this->interface->getHintForIndex(1);
        $this->assertSame(Standalone::class, $a);
    }

    public function testHint()
    {
        $this->interface->parse([
            'hint $standaloneInterface' => Standalone::class,
        ]);
        $a = $this->interface->getHintForIndex(0);
        $this->assertSame(Standalone::class, $a);
    }

    public function testHintByIndex()
    {
        $this->interface->parse([
            'hint #0' => Standalone::class,
        ]);
        $a = $this->interface->getHintForIndex(0);
        $this->assertSame(Standalone::class, $a);
    }

    public function testParamForClassAliasNotFound()
    {
        $this->expectException(ParameterConfigException::class);
        $msg = sprintf('Cannot apply alias %s::class => %s::class. No matching parameter found.', StandaloneInterface::class, Standalone::class);
        $this->expectExceptionMessage($msg);
        $this->builtins->parse([StandaloneInterface::class => Standalone::class]);
    }

    public function testHintDuplicate()
    {
        $this->expectException(ParameterConfigException::class);
        $msg = sprintf('The parameter $standaloneInterface is ambiguously aliased by a) %s::class => %s::class and b) \'hint $standaloneInterface\' => %s::class.', StandaloneInterface::class, Standalone::class, Standalone::class);
        $this->expectExceptionMessage($msg);
        $this->interface->parse([
            'hint $standaloneInterface' => Standalone::class,
            StandaloneInterface::class => Standalone::class
        ]);
    }

    public function testParamValueDuplicate()
    {
        $this->expectException(ParameterConfigException::class);
        $this->expectExceptionMessage('Parameters #0 and $int refer to the same position.');
        $this->builtins->parse(['$int' => 123, '#0' => 456]);
    }

    public function testSpreadValueNotIterable()
    {
        $this->expectException(ParameterConfigException::class);
        $this->expectExceptionMessage('Cannot spread value of type integer for parameter ...$rest, value must be iterable.');
        $this->rest->parse(['...$rest' => 123]);
    }

    public function testSpreadNonVariadic()
    {
        $this->expectException(ParameterConfigException::class);
        $this->expectExceptionMessage('Cannot spread non-variadic parameter $int.');
        $this->builtins->parse(['...$int' => [1, 2, 3]]);
    }

    public function testSpread()
    {
        $this->rest->parse(['...$rest' => [1, 2, 3]]);
        $this->assertSame(1, $this->rest->getValueForIndex(0));
        $this->assertSame(2, $this->rest->getValueForIndex(1));
        $this->assertSame(3, $this->rest->getValueForIndex(2));
    }

    public function testHintVariadic()
    {
        $this->expectException(ParameterConfigException::class);
        $this->expectExceptionMessage('Cannot hint variadic parameter ...$rest.');
        $this->rest->parse(['hint $rest' => StandaloneInterface::class]);
    }

    public function testHintNotAssignable()
    {
        $this->expectException(ParameterConfigException::class);
        $this->expectExceptionMessage('Cannot hint parameter $standalone as ' . StandaloneInterface::class . ', the type is not assignable to the existing parameter type ' . Standalone::class . '.');
        $this->class->parse(['hint $standalone' => StandaloneInterface::class]);
    }

    public function testHintRedundant()
    {
        $this->expectException(ParameterConfigException::class);
        $this->expectExceptionMessage('Parameter hint $int as int is redundant.');
        $this->builtins->parse(['hint $int' => 'int']);
    }

    public function testAlreadyHintedClass()
    {
        $this->expectException(ParameterConfigException::class);
        $this->expectExceptionMessage('Cannot hint parameter $standalone as int, the type is not assignable to the existing parameter type ' . Standalone::class . '.');
        $this->class->parse(['hint $standalone' => 'int']);
    }

    public function testAlreadyHinted()
    {
        $this->expectException(ParameterConfigException::class);
        $this->expectExceptionMessage('Cannot hint parameter #0 as bool, the type is not assignable to the existing parameter type int.');
        $this->builtins->parse(['hint #0' => 'bool']);
    }

    public function testInvalidIntegerKey()
    {
        $this->expectException(ParameterConfigException::class);
        $this->builtins->parse(['$a' => 'a', 0 => 1]);
    }

    public function testUnknownKey()
    {
        $this->expectException(ParameterConfigException::class);
        $this->builtins->parse(['hint $' => 'a']);
    }

    public function testArrayArgs()
    {
        $this->builtins->parse([1, 2, 3]);
        $this->assertFalse($this->builtins->isEmpty());
        $this->assertTrue($this->builtins->hasValueForIndex(0));
        $this->assertTrue($this->builtins->hasValueForIndex(1));
        $this->assertTrue($this->builtins->hasValueForIndex(2));
        $this->assertFalse($this->builtins->hasValueForIndex(3));
    }

    public function testTooManyArrayArgs()
    {
        $this->expectException(ParameterConfigException::class);
        $this->expectExceptionMessage('You provided 8 parameters, but only 7 are available.');
        $this->builtins->parse([1, 2, 3, 4, 5, 6, 7, 8]);
    }

    public function testIndexedArg()
    {
        $this->builtins->parse(['#0' => 1, '#1' => 2]);
        $this->assertTrue($this->builtins->hasValueForIndex(0));
        $this->assertTrue($this->builtins->hasValueForIndex(1));
    }

    public function testIndexOutOfRange()
    {
        $this->expectException(ParameterConfigException::class);
        $this->expectExceptionMessage('Parameter #7 is out of range.');
        $this->builtins->parse(['#7' => 8]);
    }

    public function testNamedArgs()
    {
        $this->builtins->parse(['$int' => 123, '$string' => 'str', '$float' => 0.5]);
        $this->assertFalse($this->builtins->isEmpty());
        $this->assertTrue($this->builtins->hasValueForIndex(0));
        $this->assertTrue($this->builtins->hasValueForIndex(1));
        $this->assertTrue($this->builtins->hasValueForIndex(2));
    }

    public function testNameDoesNotExist()
    {
        $this->expectException(ParameterConfigException::class);
        $this->expectExceptionMessage('Parameter $doesNotExist does not exist.');
        $this->builtins->parse(['$doesNotExist' => 123]);
    }

    public function testEmpty()
    {
        $this->builtins->parse([]);
        $this->assertTrue($this->builtins->isEmpty());
    }

    public function testToString()
    {
        $this->builtins->parse([
            '$int' => 123,
            '$float' => 123.0,
            '$bool' => true,
            '$array' => [1, 2, 3]
        ]);
        $this->assertTrue(strlen($this->builtins->__toString()) > 0);
    }

    protected function setUp()
    {
        $infos = new ParametersInfo();
        $infos->parse(new ReflectionMethod(Methods::class, 'int_float_string_bool_array_resource_callable_Arguments'));
        $this->builtins = new ParametersConfig($infos);

        $infos = new ParametersInfo();
        $infos->parse(new ReflectionMethod(Methods::class, 'classArgument'));
        $this->class = new ParametersConfig($infos);

        $infos = new ParametersInfo();
        $infos->parse(new ReflectionMethod(Methods::class, 'variadicArgument'));
        $this->rest = new ParametersConfig($infos);

        $infos = new ParametersInfo();
        $infos->parse(new ReflectionMethod(Methods::class, 'interfaceArgument'));
        $this->interface = new ParametersConfig($infos);
    }


    protected function tearDown()
    {
        $this->builtins = null;
        $this->class = null;
    }


}