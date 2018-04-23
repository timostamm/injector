<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 08:40
 */

namespace TS\DependencyInjection\TestSubjects\Circular;


class SelfDepending
{

    public $selfDepending;

    public function __construct(SelfDepending $selfDepending)
    {
        $this->selfDepending = $selfDepending;
    }

}