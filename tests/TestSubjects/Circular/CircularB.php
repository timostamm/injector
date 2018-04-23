<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 08:40
 */

namespace TS\DependencyInjection\TestSubjects\Circular;


class CircularB
{

    public $c;

    public function __construct(CircularC $c)
    {
        $this->c = $c;
    }

}