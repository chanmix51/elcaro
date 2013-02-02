<?php // show_employee.php

$connection = require(__DIR__."/bootstrap.php");

// CONTROLLER

if (!$employee = $connection
    ->getMapFor('\ElCaro\Company\Employee')
    ->findByPk(array('employee_id' => $_GET['employee_id'])))
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
      <li>Status: <?php echo $employee["is_manager"] ? "manager" : "worker" ?>.</li>
      <li>Department: <?php echo $employee["department_id"] ?>.</li>
    </ul>
  </body>
</html>

