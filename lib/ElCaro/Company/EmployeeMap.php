<?php

namespace ElCaro\Company;

use ElCaro\Company\Base\EmployeeMap as BaseEmployeeMap;
use ElCaro\Company\Employee;
use \Pomm\Exception\Exception;
use \Pomm\Query\Where;

class EmployeeMap extends BaseEmployeeMap
{
    public function initialize()
    {
        parent::initialize();
        $this->addVirtualField('age', 'interval');
    }

    public function getSelectFields($alias = null)
    {
        $fields = parent::getSelectFields($alias);
        $fields['age'] = 'age(birth_date)';

        return $fields;
    }

    public function getEmployeeWithDepartment($employee_id)
    {
        $department_map = $this->connection->getMapFor('\ElCaro\Company\Department');
        $sql = <<<SQL
SELECT
  %s,
  dept.name AS department_name
FROM
  %s emp
    NATURAL JOIN %s dept
WHERE
    emp.employee_id = ?
SQL;

        $sql = sprintf($sql,
            $this->formatFieldsWithAlias('getSelectFields', 'emp'),
            $this->getTableName(),
            $department_map->getTableName()
        );

        return $this->query($sql, array($employee_id))->current();
    }
}
