<?php

$loader = require __DIR__."/vendor/autoload.php";
$loader->add(null, __DIR__."/lib");

$database = new Pomm\Connection\Database(array('dsn' => 'pgsql://greg/greg', 'name' => 'elcaro'));

return $database->getConnection();
