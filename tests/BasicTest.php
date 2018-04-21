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

class BasicTest extends InjectorTest
{

    public function testInstantiate()
    {
        $subject = $this->injector->instantiate(Standalone::class);
        $this->assertInstanceOf(Standalone::class, $subject);
    }

    public function testInstantiateNonExistent()
    {
        $this->expectException(InjectionException::class);
        $this->injector->instantiate('This_does_not_exist');
    }

    public function testInstantiateInterface()
    {
        $this->expectException(InjectionException::class);
        $this->injector->instantiate(StandaloneInterface::class);
    }

    public function testInstantiateAbstract()
    {
        $this->expectException(InjectionException::class);
        $this->injector->instantiate(AbstractService::class);
    }


}