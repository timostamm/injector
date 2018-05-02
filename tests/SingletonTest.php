<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 08:39
 */

namespace TS\DependencyInjection;


use TS\DependencyInjection\Exception\InjectionException;
use TS\DependencyInjection\Exception\InjectorConfigException;
use TS\DependencyInjection\TestSubjects\MixedArgumentService;
use TS\DependencyInjection\TestSubjects\Standalone;
use TS\DependencyInjection\TestSubjects\StandaloneInterface;

class SingletonTest extends InjectorTest
{

    public function testAliased()
    {
        $this->injector->alias(StandaloneInterface::class, Standalone::class);
        $this->injector->singleton(Standalone::class);
        $a = $this->injector->instantiate(StandaloneInterface::class);
        $b = $this->injector->instantiate(StandaloneInterface::class);
        $this->assertSame($a, $b);
    }

    public function testShared()
    {
        $this->injector->singleton(Standalone::class);
        $a = $this->injector->instantiate(Standalone::class);
        $b = $this->injector->instantiate(Standalone::class);
        $this->assertSame($a, $b);
    }


    public function testRegisterTwice()
    {
        $this->injector->singleton(Standalone::class);
        $this->injector->singleton(Standalone::class);
        $this->injector->instantiate(Standalone::class);
        $this->expectException(InjectorConfigException::class);
        $this->injector->singleton(Standalone::class);
    }


    public function testInstantiationParams()
    {
        $this->injector->singleton(Standalone::class);
        $this->expectException(InjectionException::class);
        $this->injector->instantiate(Standalone::class, ['$x' => 123]);
    }


    public function testSingletonParams()
    {
        $this->injector->singleton(MixedArgumentService::class, ['$a' => 123]);
        $s = $this->injector->instantiate(MixedArgumentService::class);
        $this->assertSame(123, $s->a);
    }


}