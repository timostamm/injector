<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 10:53
 */

namespace TS\DependencyInjection\Injector;


use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use TS\DependencyInjection\Exception\ParameterConfigException;
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

    /**
     * @var ParametersConfig
     */
    protected $untyped;



    public function testValueNotAssignable_int_string()
    {
        $this->expectException(ParameterConfigException::class);
        $this->builtins->parse([
            '$int' => "str",
        ]);
    }

    public function testValueNotAssignable_int_object()
    {
        $this->expectException(ParameterConfigException::class);
        $this->builtins->parse([
            '$int' => new Standalone(),
        ]);
    }

    public function testValueNotAssignable_object_str()
    {
        $this->expectException(ParameterConfigException::class);
        $this->interface->parse([
            '$standaloneInterface' => "xxx",
        ]);
    }

    public function testInvalidNullValue()
    {
        $this->expectException(ParameterConfigException::class);
        $this->interface->parse([
            '$standaloneInterface' => null,
        ]);
    }

    public function testHintAndValueColission()
    {
        $this->expectException(ParameterConfigException::class);
        $this->interface->parse([
            'hint $standaloneInterface' => Standalone::class,
            '$standaloneInterface' => new Standalone()
        ]);
    }

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
        $a = $this->interface->getValue("standaloneInterface");
        $this->assertSame($e, $a);
    }

    public function testAlias()
    {
        $this->interface->parse([
            StandaloneInterface::class => Standalone::class
        ]);
        $a = $this->interface->getType('standaloneInterface');
        $this->assertSame(Standalone::class, $a);
        $a = $this->interface->getType('optionalStandaloneInterface');
        $this->assertSame(Standalone::class, $a);
    }

    public function testHint()
    {
        $this->interface->parse([
            'hint $standaloneInterface' => Standalone::class,
        ]);
        $a = $this->interface->getType('standaloneInterface');
        $this->assertSame(Standalone::class, $a);
    }

    public function testHintByIndex()
    {
        $this->interface->parse([
            'hint #0' => Standalone::class,
        ]);
        $a = $this->interface->getType('standaloneInterface');
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
        $this->expectExceptionMessage('Cannot spread value of type int for parameter ...$rest, value must be iterable.');
        $this->rest->parse(['...$rest' => 123]);
    }

    public function testSpreadNonVariadic()
    {
        $this->expectException(ParameterConfigException::class);
        $this->expectExceptionMessage('Cannot spread parameter $int, it is not a rest parameter.');
        $this->builtins->parse(['...$int' => [1, 2, 3]]);
    }

    public function testSpread()
    {
        $rest = [1, 2, 3];
        $this->rest->parse(['...$rest' => $rest]);
        $this->assertEquals($rest, $this->rest->getValue('rest'));
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


    public function testAlreadyHinted_builtin()
    {
        $this->expectException(ParameterConfigException::class);
        $this->expectExceptionMessage('Cannot hint parameter $int as bool, the type is not assignable to the existing parameter type int.');
        $this->builtins->parse(['hint #0' => 'bool']);
    }

    public function testAlreadyHinted_class()
    {
        $this->expectException(ParameterConfigException::class);
        $this->expectExceptionMessage('Cannot hint parameter $standalone as int, the type is not assignable to the existing parameter type ' . Standalone::class . '.');
        $this->class->parse(['hint $standalone' => 'int']);
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
        $this->builtins->parse([1, 0.5, 'str']);
        $this->assertFalse($this->builtins->isEmpty());
        $this->assertTrue($this->builtins->hasValue('int'));
        $this->assertTrue($this->builtins->hasValue('float'));
        $this->assertTrue($this->builtins->hasValue('string'));
    }

    public function testArrayArgsNonAssignable_string_int()
    {
        $this->expectException(ParameterConfigException::class);
        $this->builtins->parse([1, 2, 3]);
    }

    public function testTooManyArrayArgs()
    {
        $this->expectException(ParameterConfigException::class);
        $this->expectExceptionMessage('You provided 7 parameters, but only 6 are available.');
        $this->builtins->parse([1, 2, 3, 4, 5, 6, 7]);
    }

    public function testIndexedArg()
    {
        $this->builtins->parse(['#0' => 1, '#1' => 2]);
        $this->assertTrue($this->builtins->hasValue('int'));
        $this->assertTrue($this->builtins->hasValue('float'));
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
        $this->assertTrue($this->builtins->hasValue('int'));
        $this->assertTrue($this->builtins->hasValue('string'));
        $this->assertTrue($this->builtins->hasValue('float'));
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

    public function testToString_builtins()
    {
        $this->builtins->parse([
            '$int' => 123,
            '$float' => 123.0,
            '$bool' => true,
            '$array' => [1, 2, 3]
        ]);
        $this->assertEquals('ParametersConfig($int = 123, $float = 123.000000, $string = ?, $bool = true, $array = array(3), $callable = ?)', $this->builtins->__toString());
    }

    public function testToString_hint_interface()
    {
        $this->interface->parse([
            'hint $standaloneInterface' => Standalone::class
        ]);
        $this->assertEquals('ParametersConfig(hint $standaloneInterface as TS\DependencyInjection\TestSubjects\Standalone::class)', $this->interface->__toString());
    }

    public function testToString_untyped()
    {
        $this->untyped->parse([
            'hint $untyped' => 'int'
        ]);
        $this->assertEquals('ParametersConfig(hint $untyped as int)', $this->untyped->__toString());
    }


    protected function setUp()
    {
        $this->builtins = $this->setupConfig(Methods::class, 'int_float_string_bool_array_callable_Arguments');
        $this->class = $this->setupConfig(Methods::class, 'classArgument');
        $this->rest = $this->setupConfig(Methods::class, 'variadicArgument');
        $this->interface = $this->setupConfig(Methods::class, 'interfaceArgument');
        $this->untyped = $this->setupConfig(Methods::class, 'untypedArgument');
    }

    protected function setupConfig(string $class, string $method):ParametersConfig
    {
        $info = new ParametersInfo();
        $info->parse(new ReflectionMethod($class, $method));
        return new ParametersConfig($info);
    }




}