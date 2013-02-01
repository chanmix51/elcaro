<?php

namespace ElCaro\Company\Base;

use \Pomm\Object\BaseObjectMap;
use \Pomm\Exception\Exception;

abstract class DepartmentMap extends BaseObjectMap
{
    public function initialize()
    {

        $this->object_class =  'ElCaro\Company\Department';
        $this->object_name  =  'company.department';

        $this->addField('department_id', 'int4');
        $this->addField('name', 'varchar');
        $this->addField('parent_id', 'int4');

        $this->pk_fields = array('department_id');
    }
}