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

class InstantiateTest extends InjectorTest
{

    public function testInstantiate()
    {
        $subject = $this->injector->instantiate(Standalone::class);
        $this->assertInstanceOf(Standalone::class, $subject);
    }


}