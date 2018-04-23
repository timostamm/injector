<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 08:40
 */

namespace TS\DependencyInjection\TestSubjects\Circular;


class CircularA
{

    public $b;

    public function __construct(CircularB $b)
    {
        $this->b = $b;
    }

}