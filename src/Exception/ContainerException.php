<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 03.05.18
 * Time: 10:58
 */

namespace TS\DependencyInjection\Exception;


use Psr\Container\ContainerExceptionInterface;

class ContainerException extends InjectionException implements ContainerExceptionInterface
{

}