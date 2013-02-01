<?php
 
$connection = require(__DIR__."/bootstrap.php");
 
$scan = new Pomm\Tools\ScanSchemaTool(array(
    'schema'     => 'company',
    'database'   => $connection->getDatabase(),
    'prefix_dir' => __DIR__."/lib"
    ));
$scan->execute();
$scan->getOutputStack()->setLevel(254);
 
foreach ( $scan->getOutputStack() as $line )
{
    printf("%s\n", $line);
}
