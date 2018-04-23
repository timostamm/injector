<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 23.04.18
 * Time: 15:28
 */

namespace TS\DependencyInjection\TestSubjects\Automotive;


class Car
{

    public $engine;

    public function __construct(EngineInterface $engine)
    {
        $this->engine = $engine;
    }

}