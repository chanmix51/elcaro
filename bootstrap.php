<?php // bootstrap.php

$loader = require __DIR__."/vendor/autoload.php";
$loader->add(null, __DIR__."/lib");

$database = new Pomm\Connection\Database(array('dsn' => 'pgsql://greg/greg', 'name' => 'el_caro'));

return $database
      ->registerConverter(
          'Department', 
          new \Pomm\Converter\PgEntity($database->getConnection()->getMapFor('\ElCaro\Company\Department')), 
          array('company.department')
      )
      ->getConnection();
