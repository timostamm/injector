<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 23.04.18
 * Time: 20:52
 */

namespace TS\DependencyInjection;


use TS\DependencyInjection\Injector\ArgumentInspectionInterface;

interface InspectableInjectorInterface extends InjectorInterface
{

    function inspectInvocation(callable $callable): ArgumentInspectionInterface;

    function inspectInstantiation(string $className): ArgumentInspectionInterface;

}