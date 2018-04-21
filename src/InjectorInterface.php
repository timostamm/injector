<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 19.04.18
 * Time: 00:09
 */

namespace TS\DependencyInjection;


interface InjectorInterface
{
    function invoke(callable $callable, array $params = null);

    function instantiate(string $classname, array $params = null);


}