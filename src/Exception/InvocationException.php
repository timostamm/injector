<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 09:31
 */

namespace TS\DependencyInjection\Exception;
use ReflectionClass;
use RuntimeException;


class InvocationException extends RuntimeException implements InjectionException
{


}