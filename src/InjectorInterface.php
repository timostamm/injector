<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 19.04.18
 * Time: 00:09
 */

namespace TS\DependencyInjection;


use Psr\Container\ContainerInterface;

interface InjectorInterface extends ContainerInterface
{

    function invoke(callable $callable, array $params = null);

    function instantiate(string $resolvedClass, array $params = null);


}