<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 08:39
 */

namespace TS\DependencyInjection;


use TS\DependencyInjection\TestSubjects\Automotive\Car;
use TS\DependencyInjection\TestSubjects\Automotive\ElectricEngine;
use TS\DependencyInjection\TestSubjects\Automotive\EngineInterface;

class InvokeTest extends InjectorTest
{

    public function testInvoke()
    {
        $result = $this->injector->invoke(function(int $a, int $b){
            return $a + $b;
        }, [1, 2]);

        $this->assertEquals(3, $result);
    }


    public function testInvokeCar()
    {
        $this->injector->alias(EngineInterface::class, ElectricEngine::class);
        $car = $this->injector->invoke(function(Car $car){
            return $car;
        }, []);
        $this->assertInstanceOf(ElectricEngine::class, $car->engine);
    }


}