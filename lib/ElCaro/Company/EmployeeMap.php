<?php // lib/ElCaro/Company/EmployeeMap.php

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
        $this->addVirtualField('departments', 'company.department[]');
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
      SELECT :department_fields FROM :department_table NATURAL JOIN :employee_table emp WHERE emp.employee_id = ?
    UNION ALL
      SELECT :department_alias FROM depts parent JOIN :department_table d ON parent.parent_id = d.department_id
  )
SELECT
  :employee_alias, array_agg(depts) AS departments
FROM
  :employee_table emp,
  depts
WHERE
    emp.employee_id = ?
GROUP BY
  :group_by
SQL;

        $sql = strtr($sql, array(
            ':department_fields' => $department_map->formatFields('getSelectFields'),
            ':department_alias' => $department_map->formatFields('getSelectFields', 'd'),
            ':department_table' => $department_map->getTableName(),
            ':employee_alias' => $this->formatFieldsWithAlias('getSelectFields', 'emp'),
            ':employee_table' => $this->getTableName(),
            ':group_by' => $this->formatFields('getGroupByFields', 'emp'),
        ));

        return $this->query($sql, array($employee_id, $employee_id))->current();
    }
}
