<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 08:39
 */

namespace TS\DependencyInjection;


use TS\DependencyInjection\Exception\InstantiationException;
use TS\DependencyInjection\TestSubjects\AbstractService;
use TS\DependencyInjection\TestSubjects\Standalone;
use TS\DependencyInjection\TestSubjects\StandaloneInterface;

class BasicTest extends InjectorTest
{

    public function testInstantiate()
    {
        $subject = $this->injector->instantiate(Standalone::class);
        $this->assertInstanceOf(Standalone::class, $subject);
    }

    public function testInstantiateNonExistant()
    {
        $this->expectException(InstantiationException::class);
        $this->injector->instantiate('This_does_not_exist');
    }

    public function testInstantiateInterface()
    {
        $this->expectException(InstantiationException::class);
        $this->injector->instantiate(StandaloneInterface::class);
    }

    public function testInstantiateAbstract()
    {
        $this->expectException(InstantiationException::class);
        $this->injector->instantiate(AbstractService::class);
    }


}