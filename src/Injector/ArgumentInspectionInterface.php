<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 23.04.18
 * Time: 17:28
 */

namespace TS\DependencyInjection\Injector;


interface ArgumentInspectionInterface
{


    const TYPE_UNTYPED = 2;
    const TYPE_BUILTIN = 4;
    const TYPE_CLASS = 8;

    
    /**
     * Get the type of an argument.
     *
     * May be NULL if untyped, a built-in type or a class name.
     *
     */
    public function getType(string $name): ?string;


    /**
     * Get the names of optional arguments that are not yet provided.
     */
    public function getOptional(int $type = self::TYPE_BUILTIN | self::TYPE_UNTYPED | self::TYPE_CLASS): array;


    /**
     * Get the names of missing arguments.
     */
    public function getMissing(int $type = self::TYPE_BUILTIN | self::TYPE_UNTYPED | self::TYPE_CLASS): array;

}