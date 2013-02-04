<?php // employee_change_status.php

$connection = require(__DIR__."/bootstrap.php");

// CONTROLLER
if (!$employee = $connection->getMapFor('\ElCaro\Company\Employee')
    ->updateByPk(array('employee_id' => $_GET['employee_id']), array("is_manager" => $_GET["status"] == 0)))
{
    printf("No such employee !"); exit;
}

header(sprintf("Location: show_employee.php?employee_id=%d", $employee["employee_id"]));
