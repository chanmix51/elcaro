<?php

namespace Elcaro\Company\Base;

use \Pomm\Object\BaseObjectMap;
use \Pomm\Exception\Exception;

abstract class WorkerMap extends BaseObjectMap
{
    public function initialize()
    {

        $this->object_class =  'Elcaro\Company\Worker';
        $this->object_name  =  'company.worker';

        $this->addField('id', 'int4');
        $this->addField('first_name', 'varchar');
        $this->addField('last_name', 'varchar');
        $this->addField('department_id', 'int4');
        $this->addField('day_salary', 'int4');
        $this->addField('birth_date', 'date');

        $this->pk_fields = array('id');
    }
}