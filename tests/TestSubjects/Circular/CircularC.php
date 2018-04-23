<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 08:40
 */

namespace TS\DependencyInjection\TestSubjects\Circular;


class CircularC
{

    public $a;

    public function __construct(CircularA $a)
    {
        $this->a = $a;
    }

}