<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 08:39
 */

namespace TS\DependencyInjection;


use TS\DependencyInjection\Exception\InjectionException;
use TS\DependencyInjection\TestSubjects\AbstractService;
use TS\DependencyInjection\TestSubjects\Standalone;
use TS\DependencyInjection\TestSubjects\StandaloneInterface;

class InstantiateParametersTest extends InjectorTest
{

/*
    public function testMixedArgument()
    {
        $s = $this->injector->instantiate(MixedArgumentService::class, ['$a' => 123]);
        $this->assertSame(123, $s->a);
    }

    public function testSuperfluousValue()
    {
        $subject = $this->injector->instantiate(Standalone::class, ['$extra' => 123]);
        $this->assertInstanceOf(Standalone::class, $subject);
    }
*/


}