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
use TS\DependencyInjection\TestSubjects\Automotive\ElectricEngine;
use TS\DependencyInjection\TestSubjects\Automotive\EngineInterface;
use TS\DependencyInjection\TestSubjects\Automotive\GasolineEngine;
use TS\DependencyInjection\TestSubjects\Standalone;

class InstantiateTest extends InjectorTest
{

    public function testInstantiateStandalone()
    {
        $subject = $this->injector->instantiate(Standalone::class);
        $this->assertInstanceOf(Standalone::class, $subject);
    }


    public function testInstantiateCar()
    {
        $this->injector->alias(EngineInterface::class, ElectricEngine::class);
        $subject = $this->injector->instantiate(Car::class);
        $this->assertInstanceOf(Car::class, $subject);
    }

    public function testInstantiateCarWithDefaults()
    {
        $this->injector->defaults(Car::class, [
            '$engine' => new ElectricEngine()
        ]);
        $car = $this->injector->instantiate(Car::class);
        $this->assertInstanceOf(ElectricEngine::class, $car->engine);
    }


    public function testInstantiateCarWithOverriddenDefaults()
    {
        $this->injector->defaults(Car::class, [
            '$engine' => new ElectricEngine()
        ]);
        $car = $this->injector->instantiate(Car::class, [
            '$engine' => new GasolineEngine()
        ]);
        $this->assertInstanceOf(GasolineEngine::class, $car->engine);
    }


    public function testDecorate()
    {
        $this->injector->defaults(Car::class, [
            '$engine' => new ElectricEngine()
        ]);

        $this->injector->decorate(Car::class, function(Car $car, Standalone $standalone){
            $car->engine = new GasolineEngine();
            $this->assertNotNull($standalone);
        });

        $car = $this->injector->instantiate(Car::class);
        $this->assertInstanceOf(GasolineEngine::class, $car->engine);
    }


    public function testFactory()
    {
        $this->injector->factory(EngineInterface::class, function():EngineInterface{
            return new GasolineEngine();
        });
        $engine = $this->injector->instantiate(EngineInterface::class);
        $this->assertNotNull($engine);
        $this->assertInstanceOf(EngineInterface::class, $engine);
        $this->assertInstanceOf(GasolineEngine::class, $engine);
    }

    public function testInvalidFactory()
    {
        $this->injector->factory(EngineInterface::class, function(){
            return new Car(new GasolineEngine());
        });
        $this->expectException(InjectionException::class);
        $this->injector->instantiate(EngineInterface::class);
    }


}