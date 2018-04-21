<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 20.04.18
 * Time: 20:12
 */

namespace TS\DependencyInjection\Reflection;


class Parameter
{


    public function getName():string
    {}

    public function hasType():bool
    {}

    public function hasBuiltinType():bool
    {}

    public function hasUserType():bool
    {}

    // mixed for no type
    public function getType():string
    {}


}