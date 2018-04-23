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
use TS\DependencyInjection\Exception\ArgumentListException;
use TS\DependencyInjection\Reflection\ParametersInfo;
use TS\DependencyInjection\TestSubjects\Methods;
use TS\DependencyInjection\TestSubjects\Standalone;


class ArgumentListTest extends TestCase
{


    public function test_values_throws_missing()
    {
        $args = $this->setupArgumentList(
            Methods::class,
            'classArgument',
            []
        );
        $this->expectException(ArgumentListException::class);
        $args->values();
    }

    public function test_values_uses_configs()
    {
        $a = new Standalone();
        $b = new Standalone();
        $args = $this->setupArgumentList(
            Methods::class,
            'classArgument',
            ['$standalone' => $a],
            ['$standalone' => $b]
        );
        $this->assertEmpty($args->getMissing());
        $this->assertEquals([$b], $args->values());
    }

    public function test_values_rest()
    {
        $args = $this->setupArgumentList(
            Methods::class,
            'variadicArgument',
            ['$rest' => [1,2,3]]
        );
        $this->assertEquals([1,2,3], $args->values());
    }

    public function testGetOptional()
    {
        $args = $this->setupArgumentList(
            Methods::class,
            'classArgument',
            ['$standalone' => new Standalone()]
        );
        $this->assertEquals(['optionalStandalone'], $args->getOptional());
        $this->assertEquals(['optionalStandalone'], $args->getOptional(ArgumentList::TYPE_CLASS));
        $this->assertEmpty($args->getOptional(ArgumentList::TYPE_BUILTIN));
        $this->assertEmpty($args->getOptional(ArgumentList::TYPE_UNTYPED));
    }


    public function testGetMissing()
    {
        $args = $this->setupArgumentList(
            Methods::class,
            'int_float_string_bool_array_callable_Arguments',
            ['$int' => 123]
        );
        $this->assertEquals(['float', 'string', 'bool', 'array', 'callable'], $args->getMissing(ArgumentList::TYPE_BUILTIN));
        $this->assertEquals(['float', 'string', 'bool', 'array', 'callable'], $args->getMissing());
        $this->assertEmpty($args->getMissing(ArgumentList::TYPE_CLASS));
        $this->assertEmpty($args->getMissing(ArgumentList::TYPE_UNTYPED));

        $args = $this->setupArgumentList(
            Methods::class,
            'classArgument',
            ['$optionalStandalone' => new Standalone()]
        );
        $this->assertEquals(['standalone'], $args->getMissing());
        $this->assertEquals(['standalone'], $args->getMissing(ArgumentList::TYPE_CLASS));
        $this->assertEmpty($args->getMissing(ArgumentList::TYPE_BUILTIN));
        $this->assertEmpty($args->getMissing(ArgumentList::TYPE_UNTYPED));

        $args = $this->setupArgumentList(
            Methods::class,
            'classArgument',
            ['$optionalStandalone' => new Standalone()],
            ['$standalone' => new Standalone()]
        );
        $this->assertEmpty($args->getMissing());
    }


    public function testProvideMissingValue()
    {
        $args = $this->setupArgumentList(
            Methods::class,
            'int_float_string_bool_array_callable_Arguments',
            ['$int' => 123]
        );
        $this->assertEquals(['float', 'string', 'bool', 'array', 'callable'], $args->getMissing());

        $args->setValue('float', 0.5);
        $this->assertEquals(['string', 'bool', 'array', 'callable'], $args->getMissing());
    }


    public function testProvideMissingValue_notAssignable()
    {
        $args = $this->setupArgumentList(
            Methods::class,
            'int_float_string_bool_array_callable_Arguments'
        );
        $this->expectException(ArgumentListException::class);
        $args->setValue('int', "str");
    }



    public function testProvideMissingValue_parameterNotFound()
    {
        $args = $this->setupArgumentList(
            Methods::class,
            'int_float_string_bool_array_callable_Arguments'
        );
        $this->expectException(ArgumentListException::class);
        $args->setValue('doesNotExist', "str");
    }



    protected function setupArgumentList(string $class, string $method, ...$configs):ArgumentList
    {
        $info = new ParametersInfo();
        $info->parse(new ReflectionMethod($class, $method));
        $args = new ArgumentList($info);
        foreach ($configs as $params) {
            $config = $this->setupConfig($class, $method);
            $config->parse($params);
            $args->addConfig($config);
        }
        return $args;
    }

    protected function setupConfig(string $class, string $method):ParametersConfig
    {
        $info = new ParametersInfo();
        $info->parse(new ReflectionMethod($class, $method));
        return new ParametersConfig($info);
    }




}