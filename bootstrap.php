<?php // bootstrap.php

$parameters = array(
    'login'=> 'elcaro',
    'password' => 'elcaro',
    'host' => 'localhost',
    'port' => 5432,
    'dbname' => 'ElCaro'
);

$loader = require __DIR__."/vendor/autoload.php";
$loader->add(null, __DIR__."/lib");

$database = new Pomm\Connection\Database(array('dsn' => sprintf("pgsql://%s:%s@%s:%d/%s",
    $parameters['login'], $parameters['password'], $parameters['host'], $parameters['port'], $parameters['dbname'])));

return $database
      ->registerConverter(
          'Department',
          new \Pomm\Converter\PgEntity($database->getConnection()->getMapFor('\ElCaro\Company\Department')),
          array('company.department')
      )
      ->getConnection();
