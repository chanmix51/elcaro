<?php // show_employee.php

$connection = require(__DIR__."/bootstrap.php");

// CONTROLLER

if (!$employee = $connection
    ->getMapFor('\ElCaro\Company\Employee')
    ->getEmployeeWithDepartment($_GET['employee_id']))
{
    printf("No such user.");
    exit;
}

// TEMPLATE
?>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  </head>
  <body>
    <h1>El-Caro - <?php echo $employee ?> (<?php echo $employee["employee_id"] ?>)</h1>
    <p><a href="/index.php">Back to the homepage</a>.</p>
    <ul>
      <li>Birth date: <?php echo $employee["birth_date"]->format("d/m/Y") ?>.</li>
      <li>Age: <?php echo $employee['age']->format("%y") ?> years old.</li>
      <li>day salary indice: <?php printf("%05.2f", $employee["day_salary"]) ?>.</li>
      <li>Status: <a href="employee_change_status.php?status=<?php echo $employee['is_manager'] ? 1 : 0 ?>&employee_id=<?php echo $employee["employee_id"] ?>"><?php echo $employee["is_manager"] ? "manager" : "worker" ?></a>.</li>
      <li>Departments: <?php echo join(' &gt; ', array_map(function($dept) {
          return sprintf('<a href="/show_department.php?department_id=%d" title="dept id = [%02d]">%s</a>', $dept["department_id"], $dept['department_id'], $dept["name"]); 
      }, $employee["departments"])) ?>.</li>
    </ul>
  </body>
</html>

