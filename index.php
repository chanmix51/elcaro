<?php //index.php

// CONTROLLER
$connection = require(__DIR__."/bootstrap.php");
 
$departments = $connection
    ->getMapFor('\ElCaro\Company\Department')
    ->findAll();

// TEMPLATE
?>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  </head>
  <body>
    <h1>El-Caro - Liste des départements</h1>
<?php if ($departments): ?>
    <ul>
  <?php foreach($departments as $department): ?>
      <li><a href="/list_empoyee.php?department_id=<?php echo $department["department_id"] ?>"><?php echo $department["name"] ?></a></li>
  <?php endforeach ?>
    </ul>
<?php else: ?>
    <p>No departments !?!? There must be a bug somewhere...</p>
<?php endif ?>
  </body>
</html>

