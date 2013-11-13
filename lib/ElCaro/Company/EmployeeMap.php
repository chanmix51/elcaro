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
        $fields['age'] = sprintf('age(%s)', $this->aliasField("birth_date", $alias));

        return $fields;
    }

    public function getEmployeeWithDepartment($employee_id)
    {
        $department_map = $this->connection->getMapFor('\ElCaro\Company\Department');
        $sql = <<<SQL
WITH RECURSIVE
  depts  (department_id, name, parent_id) AS (
      SELECT :department_fields_alias_d FROM :department_table d NATURAL JOIN :employee_table emp WHERE emp.employee_id = $*
    UNION ALL
      SELECT :department_fields_alias_d FROM depts parent JOIN :department_table d ON parent.parent_id = d.department_id
  )
SELECT
  :employee_fields_alias_emp, array_agg(depts) AS departments
FROM
  :employee_table emp,
  depts
WHERE
    emp.employee_id = $*
GROUP BY
  :employee_group_by_emp
SQL;

        $sql = strtr($sql, array(
            ':department_fields_alias_d' => $department_map->formatFieldsWithAlias('getSelectFields', 'd'),
            ':department_table'          => $department_map->getTableName(),
            ':employee_fields_alias_emp' => $this->formatFieldsWithAlias('getSelectFields', 'emp'),
            ':employee_table'            => $this->getTableName(),
            ':employee_group_by_emp'     => $this->formatFields('getGroupByFields', 'emp'),
        ));

        return $this->query($sql, array($employee_id, $employee_id))->current();
    }
}
