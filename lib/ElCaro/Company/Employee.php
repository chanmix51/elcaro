<?php

namespace ElCaro\Company;

use \Pomm\Object\BaseObject;
use \Pomm\Exception\Exception;

class Employee extends BaseObject
{
    public function getFirstName()
    {
        return ucwords($this->get('first_name'));
    }

    public function getLastName()
    {
        return strtoupper($this->get('last_name'));
    }

    public function __toString()
    {
        return sprintf("%s %s", $this['first_name'], $this['last_name']);
    }
}
