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
        $this->addVirtualField('department_names', 'varchar[]');
    }

    public function getSelectFields($alias = null)
    {
        $fields = parent::getSelectFields($alias);
        $alias = !is_null($alias) ? sprintf("%s.", $alias) : "";
        $fields['age'] = sprintf('age(%s"birth_date")', $alias);

        return $fields;
    }

    public function getEmployeeWithDepartment($employee_id)
    {
        $department_map = $this->connection->getMapFor('\ElCaro\Company\Department');
        $sql = <<<SQL
WITH RECURSIVE
  depts  (department_id, name, parent_id) AS (
      SELECT %s FROM %s NATURAL JOIN %s emp WHERE emp.employee_id = ?
    UNION ALL
      SELECT %s FROM depts parent JOIN %s d ON parent.parent_id = d.department_id
  )
SELECT
  %s,
  array_agg(depts.name) AS department_names
FROM
  %s emp,
  depts
WHERE
    emp.employee_id = ?
GROUP BY
  %s
;
SQL;

        $sql = sprintf($sql,
            $department_map->formatFields('getSelectFields'),
            $department_map->getTableName(),
            $this->getTableName(),
            $department_map->formatFields('getSelectFields', 'd'),
            $department_map->getTableName(),
            $this->formatFieldsWithAlias('getSelectFields', 'emp'),
            $this->getTableName(),
            $this->formatFields('getGroupByFields', 'emp')
        );

        return $this->query($sql, array($employee_id, $employee_id))->current();
    }
}
