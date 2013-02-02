<?php //index.php

// CONTROLLER
$connection = require(__DIR__."/bootstrap.php");
 
$employees = $connection
    ->getMapFor('\ElCaro\Company\Employee')
    ->findAll();

// TEMPLATE
?>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  </head>
  <body>
    <h1>El-Caro - Workers list</h1>
<?php if ($employees): ?>
    <ul>
  <?php foreach($employees as $employee): ?>
      <li><a href="/show_employee.php?employee_id=<?php echo $employee["employee_id"] ?>"><?php echo $employee ?></a></li>
  <?php endforeach ?>
    </ul>
<?php else: ?>
    <p>No employees !?!? There must be a bug somewhere...</p>
<?php endif ?>
  </body>
</html>

