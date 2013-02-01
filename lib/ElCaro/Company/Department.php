<?php

namespace ElCaro\Company;

use \Pomm\Object\BaseObject;
use \Pomm\Exception\Exception;

class Department extends BaseObject
{
    public function getName()
    {
        return ucwords($this->get('name'));
    }
}
