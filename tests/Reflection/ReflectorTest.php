<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 10:53
 */

namespace TS\DependencyInjection\Reflection;


use PHPUnit\Framework\TestCase;
use TS\DependencyInjection\TestSubjects\Methods;

class ReflectorTest extends TestCase
{


    /**
     * @dataProvider callableProvider
     */
    public function testGetCallableType($callable, string $expected)
    {
        $this->assertTrue(is_callable($callable));
        $actual = $this->reflector->getCallableType($callable);
        $this->assertEquals($expected, $actual);
    }

    public function callableProvider():array
    {
        return [
            [ 'array_key_exists', Reflector::FUNCTION ],
            [ [new Methods(), 'noArguments'], Reflector::OBJECT_METHOD ],
            [ [Methods::class, 'staticNoArguments'], Reflector::STATIC_CLASS_METHOD ],
            [ Methods::class . '::staticNoArguments', Reflector::STATIC_CLASS_METHOD ],
            [ function(){}, Reflector::CLOSURE ],
        ];
    }


    /**
     * @var Reflector
     */
    protected $reflector;

    protected function setUp()
    {
        $this->reflector = new Reflector();
    }


    protected function tearDown()
    {
        $this->reflector = null;
    }
}