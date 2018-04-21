<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 08:40
 */

namespace TS\DependencyInjection\TestSubjects;


class MixedArgumentService
{

    public $a;

    public function __construct($a)
    {
        $this->a = $a;
    }

}