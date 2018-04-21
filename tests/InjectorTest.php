<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 08:38
 */

namespace TS\DependencyInjection;


use PHPUnit\Framework\TestCase;

abstract class InjectorTest extends TestCase
{

    /**
     * @var Injector
     */
    protected $injector;

    protected function setUp()
    {
        $this->injector = new Injector();
    }


    protected function tearDown()
    {
        $this->injector = null;
    }

}