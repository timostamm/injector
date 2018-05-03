<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 08:39
 */

namespace TS\DependencyInjection;


use TS\DependencyInjection\Exception\NotFoundException;
use TS\DependencyInjection\TestSubjects\Automotive\EngineInterface;
use TS\DependencyInjection\TestSubjects\Automotive\GasolineEngine;

class PSR11Test extends InjectorTest
{

    public function testHas()
    {
        $this->assertFalse($this->injector->has(EngineInterface::class));
        $this->assertTrue($this->injector->has(GasolineEngine::class));
        $this->injector->alias(EngineInterface::class, GasolineEngine::class);
        $this->assertTrue($this->injector->has(EngineInterface::class));
    }


    public function testGet()
    {
        $this->expectException(NotFoundException::class);
        $this->injector->get(EngineInterface::class);
    }


}