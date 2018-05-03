<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 08:39
 */

namespace TS\DependencyInjection;


use TS\DependencyInjection\Exception\InjectionException;
use TS\DependencyInjection\TestSubjects\Automotive\Car;
use TS\DependencyInjection\TestSubjects\Circular\CircularA;
use TS\DependencyInjection\TestSubjects\Circular\CircularB;
use TS\DependencyInjection\TestSubjects\Circular\CircularC;
use TS\DependencyInjection\TestSubjects\Circular\SelfDepending;

class InstantiateCircularTest extends InjectorTest
{

    public function testSelfDepending()
    {
        $this->expectException(InjectionException::class);
        $this->expectExceptionMessage('Circular dependency detected: new '.SelfDepending::class.'() -> new '.SelfDepending::class.'().');
        $this->injector->instantiate(SelfDepending::class);
    }


    public function testCircularA()
    {
        $this->expectException(InjectionException::class);
        $this->expectExceptionMessage('Circular dependency detected: new '.CircularA::class.'() -> new '.CircularB::class.'() -> new '.CircularC::class.'() -> new '.CircularA::class.'().');
        $this->injector->instantiate(CircularA::class);
    }


    public function testFactory()
    {
        $this->injector->factory(Car::class, function(Car $car):Car{
            return $car;
        });
        $this->expectException(InjectionException::class);
        $this->expectExceptionMessage('Circular dependency detected: new '.Car::class.'() -> new '.Car::class.'().');
        $this->injector->instantiate(Car::class);
    }


}