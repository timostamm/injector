<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 10:53
 */

namespace TS\DependencyInjection\Reflection;


use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use TS\DependencyInjection\TestSubjects\Methods;
use TS\DependencyInjection\TestSubjects\Standalone;


class ParametersInfoTest extends TestCase
{

    /**
     * @var ParametersInfo
     */
    public $builtins;
    /**
     * @var ParametersInfo
     */
    public $rest;
    /**
     * @var ParametersInfo
     */
    public $class;


    public function testBuiltins()
    {
        $this->assertEquals('int', $this->builtins->getType('int'));
        $this->assertEquals('float', $this->builtins->getType('float'));
        $this->assertEquals('string', $this->builtins->getType('string'));
        $this->assertEquals('bool', $this->builtins->getType('bool'));
        $this->assertEquals('array', $this->builtins->getType('array'));
        $this->assertEquals('callable', $this->builtins->getType('callable'));

        $this->assertTrue($this->builtins->isTypeBuiltin('int'));
        $this->assertTrue($this->builtins->isTypeBuiltin('float'));
        $this->assertTrue($this->builtins->isTypeBuiltin('string'));
        $this->assertTrue($this->builtins->isTypeBuiltin('bool'));
        $this->assertTrue($this->builtins->isTypeBuiltin('array'));
        $this->assertTrue($this->builtins->isTypeBuiltin('callable'));

        $this->assertFalse($this->builtins->isVariadic('int'));
        $this->assertFalse($this->builtins->isVariadic('float'));
        $this->assertFalse($this->builtins->isVariadic('string'));
        $this->assertFalse($this->builtins->isVariadic('bool'));
        $this->assertFalse($this->builtins->isVariadic('array'));
        $this->assertFalse($this->builtins->isVariadic('callable'));

        $this->assertTrue($this->builtins->isRequired('int'));
        $this->assertTrue($this->builtins->isRequired('float'));
        $this->assertTrue($this->builtins->isRequired('string'));
        $this->assertTrue($this->builtins->isRequired('bool'));
        $this->assertTrue($this->builtins->isRequired('array'));
        $this->assertTrue($this->builtins->isRequired('callable'));

        $this->assertFalse($this->builtins->allowsNull('int'));
        $this->assertFalse($this->builtins->allowsNull('float'));
        $this->assertFalse($this->builtins->allowsNull('string'));
        $this->assertFalse($this->builtins->allowsNull('bool'));
        $this->assertFalse($this->builtins->allowsNull('array'));
        $this->assertFalse($this->builtins->allowsNull('callable'));

        $this->assertEquals(6, $this->builtins->count());
        $this->assertEquals(1, $this->builtins->indexOf('float'));
        $this->assertEquals('float', $this->builtins->findName(1));
        $this->assertEquals(['int', 'float', 'string', 'bool', 'array', 'callable'], $this->builtins->getNames());
        $this->assertFalse( $this->builtins->hasVariadic());
        $this->assertFalse( $this->builtins->isVariadic('int'));
        $this->assertNull( $this->builtins->getVariadic());
        $this->assertEquals(1, $this->builtins->indexOf('float'));

    }


    public function testRest()
    {
        $this->assertNull($this->rest->getType('rest'));
        $this->assertFalse($this->rest->isTypeBuiltin('rest'));
        $this->assertTrue( $this->rest->hasVariadic());
        $this->assertEquals( 'rest', $this->rest->getVariadic());
        $this->assertTrue( $this->rest->isVariadic('rest'));
        $this->assertFalse( $this->rest->isRequired('rest'));
        $this->assertTrue( $this->rest->allowsNull('rest'));
        $this->assertEquals( 1, $this->rest->count());
        $this->assertEquals( 0, $this->rest->indexOf('rest'));
    }


    public function testClass()
    {
        $this->assertEquals( 2, $this->class->count());
        $this->assertFalse( $this->class->hasVariadic());

        $this->assertEquals(Standalone::class, $this->class->getType('standalone'));
        $this->assertFalse($this->class->isTypeBuiltin('standalone'));
        $this->assertNull( $this->class->getVariadic());
        $this->assertFalse( $this->class->isVariadic('standalone'));
        $this->assertTrue( $this->class->isRequired('standalone'));
        $this->assertFalse( $this->class->allowsNull('standalone'));
        $this->assertEquals( 0, $this->class->indexOf('standalone'));

        $this->assertEquals(Standalone::class, $this->class->getType('optionalStandalone'));
        $this->assertFalse($this->class->isTypeBuiltin('optionalStandalone'));
        $this->assertNull( $this->class->getVariadic());
        $this->assertFalse( $this->class->isVariadic('optionalStandalone'));
        $this->assertFalse( $this->class->isRequired('optionalStandalone'));
        $this->assertTrue( $this->class->allowsNull('optionalStandalone'));
        $this->assertNull( $this->class->getDefaultValue('optionalStandalone'));
        $this->assertEquals( 1, $this->class->indexOf('optionalStandalone'));
    }


    protected function setUp()
    {
        $this->builtins = new ParametersInfo();
        $this->builtins->parse(new ReflectionMethod(Methods::class, 'int_float_string_bool_array_callable_Arguments'));

        $this->class = new ParametersInfo();
        $this->class->parse(new ReflectionMethod(Methods::class, 'classArgument'));

        $this->rest = new ParametersInfo();
        $this->rest->parse(new ReflectionMethod(Methods::class, 'variadicArgument'));
    }


    protected function tearDown()
    {
        $this->builtins = null;
        $this->class = null;
        $this->rest = null;
    }


}