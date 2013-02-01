<?php

namespace ElCaro\Company\Base;

use \Pomm\Object\BaseObjectMap;
use \Pomm\Exception\Exception;

abstract class EmployeeMap extends BaseObjectMap
{
    public function initialize()
    {

        $this->object_class =  'ElCaro\Company\Employee';
        $this->object_name  =  'company.employee';

        $this->addField('employee_id', 'int4');
        $this->addField('first_name', 'varchar');
        $this->addField('last_name', 'varchar');
        $this->addField('birth_date', 'date');
        $this->addField('is_manager', 'bool');
        $this->addField('day_salary', 'numeric');
        $this->addField('department_id', 'int4');

        $this->pk_fields = array('employee_id');
    }
}