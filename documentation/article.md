Pomm - PostgreSQL / PHP Object Model Manager
============================================

What is it ?
------------

[Pomm](http://pomm.coolkeums.org) is an **Object Model Manager** dedicated to the PostgreSQL relational database engine. What is an object model manager ?

It is upon all an object hydtrator using a converter between PHP and PostgreSQL to ensure a boolean in Postgres will be seen in PHP as is and so on for arrays, key -> value store HStore, geometric types, XML, JSON, etc.

This conversion functionality is really important since elements types in PostgreSQL are a pillar of the constraint based schema structure. The possibility to extend PostgreSQL types set with custom types is taken in account.

It is also an object oriented model manager since Pomm creates mapping classes linking the SQL structures with PHP objects. We will see here again major differences between Pomm and classical ORM and how to benefit from Postgesql features in a small application.

What makes Pomm different from an ORM and why to use it ?
---------------------------------------------------------

It is uneasy to quickly answer this question without falling in the Pro / Cons ORM endless flamewar. The author has been working with PHP and PostgreSQL for more than 10 years. The rising of ORM certainly changed the way how databases are used in making real model layers within the MVC but they also came with a certain number of very annoying pitfalls for programmers used to deal with relational databases. Pomm takes the side to work only with PostgreSQL and it aims at making developers to leverage the most of its rich features.

One of the main ORM limitation is the structure freeze. As the class definition is engraved into the code, it also freezes the relational structure. But, knowing that 

 * databases only manipulate [sets](http://en.wikipedia.org/wiki/Relational_algebra "relational algebra") of tuples,
 * relational operations are insensitive to the size of these tuples,
 * project system (SELECT) as been made to shape them.
 
A set in a database is everything but frozen. We will see how Pomm leverages PHP's flexibility to create elastic objects that fit our needs. This is even more significant that PostgreSQL knows how to manipulate entities like objects. We will see how to perform object oriented SQL queries.

Another ORM limitation is due to their abstraction layer: they do propose a pseudo object oriented SQL language that only provide the smallest feature set shared between database engines and it is often tedious to find how to do something we already know how to write in classical SQL. We will see how Pomm makes developers able to write SQL queries while make them to focus on the "what" instead of the "how". 

This article proposes to present the making of an application which search and display informations about employees in the "El-Caro Corporation" company.

Application setup
-----------------

The following application does not use a framework and is voluntarily simplistic. It is obviously recommended to use one. There are adapters for [Silex et Symfony](http://pomm.coolkeums.org/download). Do not be surprised not to find pretty URLs (routing), encapsulated controllers (and testable), template engine (recommended) and other good practices. This will of course be far from what a REST application would like but it will make us to focus on Pomm this way.

We are going to use [Composer](http://composer.org "Composer is good.") to install Pomm and take advantage of its autoloader. In order to do this, no more than a `composer.json` file in an empty directory is needed:

```json
{
    "minimum-stability": "dev",
    "require": {
        "pomm/pomm": "dev-master"
    }
}
```

A call to `composer.phar` script should install Pomm and create the autoloader's file.


Welcome in El-Caro Corporation
------------------------------

This tutorial aims at creating a simplistic application for managing employees of "El-Caro Corporation". This company is divided into departments structured in a tree. Each employee is attached to a department that can itself be in a department and so on. The database structure is like the following:

![Alt text](elcaro.db.png "Database Structure")

Let's create a schema named `company` where we can store the structure given below:

```sql
$> CREATE SCHEMA company;
$> SET search_path TO company, public;
$> SHOW search_path;
company, public
```

The `SHOW` command should return `company, public` to indicate the psql client will first look for objects into the `company` schema and then fall back in the default `public` schema. There are several advantages in using schemas, one of the most important is the ability to create extensions containing tables with the same name without colliding each other. Another advantage is we can delete our extension structure with `DROP SCHEMA company CASCADE` and start over again. Once the schema is created, we can implement the structure:

```sql
$> CREATE TABLE department (
    department_id       serial      PRIMARY KEY,
    name                varchar     NOT NULL,
    parent_id           integer     REFERENCES department (department_id)
    );
```

The way we described it, this table owns a technical integer identifier that auto increments trough a sequence. Notice the hierarchical structure is made using a foreign key `parent_id` to itself that can be null (root node case). Each children of the root node will have its father to exist in the table.

```sql
$> CREATE TABLE employee (
    employee_id         serial          PRIMARY KEY,
    first_name          varchar         NOT NULL,
    last_name           varchar         NOT NULL,
    birth_date          date            NOT NULL CHECK (age(birth_date) >= '18 years'::interval),
    is_manager          boolean         NOT NULL DEFAULT false,
    day_salary          numeric(7,2)    NOT NULL,
    department_id       integer         NOT NULL REFERENCES department (department_id)
    );
```

We see here the employee structure is strongly typed using constraints. For the example sake, there is a constraint on the age of the employee to prevent people less than 18 years old to be be stored in the table. Each employee has to belong to a department since the `department_id` foreign key to the `department` table cannot be null.

A data sample can be found online in [this gist](https://gist.github.com/raw/4664191/c1fbaba2c82b4d2950709ec2c208852894d16152/structure.sql "wget me").

PHP model generation
--------------------

Starting from this database structure, Pomm can generate classes that map to tables and manage PDO tedious operations. In a first time, let's create a `bootstrap.php` file that contains context initialization to be used by all of our applications scripts.

```php
<?php // bootstrap.php

$loader = require __DIR__."/vendor/autoload.php";
$loader->add(null, __DIR__."/lib");

$database = new Pomm\Connection\Database(array('dsn' => 'pgsql://greg/greg', 'name' => 'el_caro'));

return $database->getConnection();
```

Notice we do specify directory `lib` as default fall back for the autoloader to find namespaces.

To generate the mapping classes, let's create the `generate_model.php` script. There is a more general version in [this gist](https://gist.github.com/1510801#file-generate_model-php "generate_model.php").

```php
<?php //generate_model.php
 
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
```

It uses one of the tools provided by Pomm: the [schema scanner](http://pomm.coolkeums.org/documentation/manual-1.1#map-generation-tools "documentation"). This tool uses the database inspector to generate mapping classes. In this present case, we are asking it to scan the `company` schema and to generate files under the `lib` subdirectory where we pointed the autoloader. This should generate the following structure:

    lib/
    └── ElCaro
        └── Company
            ├── Base
            │   ├── DepartmentMap.php
            │   └── EmployeeMap.php
            ├── DepartmentMap.php
            ├── Department.php
            ├── EmployeeMap.php
            └── Employee.php

It should not surprise ORM users. We can see the namespace used is the name of the database given when instanciating the `Database` class and the schema name. This way, it is possible to have several classes with the same name in different namespaces. There are 3 classes per table:

 * a class with the same name as the table (capitalized) ;
 * a class with the same name and `Map` append to it ;
 * the exact same class in the `Base` sub namespace.

The classes in the `Base` sub namespace contain structure definition deducted from the database. This files are overwritten every time the database is introspected, it would be sad some of your code to be there. This is why the `Map` class inherits from its `Base` sister. This is where you can describe your model definition.

ORM users will also not be surprised to see the `Map` class is the tool that will manage their matching entity's life with the database:

 * `DepartmentMap` saves, generates and returns collections of `Department` entities ;
 * `EmployeeMap` saves, generates and returns collections of `Employee` entities.

First steps
-----------

For our first interface, we are going to display the list of all employees. Let's create the `index.php` with the following code:

```php
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
      <li><a href="/show_employee.php?employee_id=<?php echo $employee["employee_id"] ?>"><?php echo $employee["first_name"]." ".$employee["last_name"] ?></a></li>
  <?php endforeach ?>
    </ul>
<?php else: ?>
    <p>No employees !?!? There must be a bug somewhere...</p>
<?php endif ?>
  </body>
</html>
```

What do we have ?

 1. The connection provided us with map classes instance.
 2. Map class knows how to do query on their corresponding entity.
 3. Collections are traversable using `foreach` and return entities.
 4. Entities internal values can be reached using array notation.

The use of array notation is handy in templates and it is the complete equivalent of the accessor use. Hence `$employee['first_name']` is strictly equivalent to `$employee->getFirstName()`. This allows by example to overload the accessor if we want to format the name as capitalized and the last name in upper case:

```php
<?php // lib/ElCaro/Company/Employee.php

namespace ElCaro\Company;

use \Pomm\Object\BaseObject;
use \Pomm\Exception\Exception;

class Employee extends BaseObject
{
    public function getFirstName()
    {
        return ucwords($this->get('first_name'));
    }

    public function getLastName()
    {
        return strtoupper($this->get('last_name'));
    }

    public function __toString()
    {
        return sprintf("%s %s", $this['first_name'], $this['last_name']);
    }
}
```

Only generic accessors `get()`, `set()`, `has()` et `clear()` cannot be overloaded. They are used to access values returned by the database. We could need that here if we want to implement a search based on the name since the `getFirstName()` accessor does not return the value stored in the database.

In the real life™, such example would not be really useable as soon as the data volume goes over several dozens employees. It would not be more a hassle to [sort employees in alphabetical order](http://pomm.coolkeums.org/documentation/manual-1.1#findall) and/or use a [pager](http://pomm.coolkeums.org/documentation/manual-1.1#pagers).

Elastic entities
----------------

Let's focus now on single employee data display:

```php
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
      <li>day salary indice: <?php printf("%05.2f", $employee["day_salary"]) ?>.</li>
      <li>Status: <?php echo $employee["is_manager"] ? "manager" : "worker" ?>.</li>
      <li>Department: <?php echo $employee["department_id"] ?>.</li>
    </ul>
  </body>
</html>
```

Here again, we can see the converter made its job: date of birth is a PHP `DateTime` instance, the field `is_manager` is a boolean and we can format `day_salary` correctly.

Imagine now we need to display the age of the employee. It is of course possible to add a `getAge()` accessor that would compute the age from the data of birth in PHP in the `Employee` class, but why not ask this information directly from PostgreSQL ?

We must realize Pomm never uses the wild card `*` in its queries, it uses the `getSelectFields()` method defined in the map classes. By default, this method returns all table fields but is is also possible to overload it to add or filter fields. In other words, this method defines **the projection of the database object into the PHP entity**.

```php
<?php // lib/ElCaro/Company/EmployeeMap.php

namespace ElCaro\Company;

use ElCaro\Company\Base\EmployeeMap as BaseEmployeeMap;
use ElCaro\Company\Employee;
use \Pomm\Exception\Exception;
use \Pomm\Query\Where;

class EmployeeMap extends BaseEmployeeMap
{
    public function getSelectFields($alias = null)
    {
        $fields = parent::getSelectFields($alias);
        $fields['age'] = 'age(birth_date)';

        return $fields;
    }
}
```

And add the following line in the template part of `show_employee.php`:

    <li>Age: <?php echo $employee['age'] ?>.</li>

Refresh the page, it should now display something like `Age: 27 years 11 mons 2 days.`. This is the raw output of PostgreSQL's `age()` function. Since Pomm does not know how to interpret this, it converts it as a string. It is possible to extend our entity's definition in adding the type of this new virtual column to make it handled by the converter system when it exists:

```php
<?php // lib/ElCaro/Company/EmployeeMap.php
// [...]

    public function initialize()
    {
        parent::initialize();
        $this->addVirtualField('age', 'interval');
    }
```

If you now refresh the page, it displays an error as PHP does not know how to display a `DateInterval` instance: the converter has made a good job. Change the template following line:

      <li>Age: <?php echo $employee['age']->format("%y") ?> years old.</li>

Before we conclude this part, it is important to say the `getSelectFields()` method we did overload will cause problems as the new `age` column does not take the optional alias in account. This is mandatory when complex queries use sets with similar column names and will lead to `Ambiguous field` type errors. To ovoid this, let's fix our method:

```php
    public function getSelectFields($alias = null)
    {
        $fields = parent::getSelectFields($alias);
        $alias = is_null($alias) ? "" : sprintf("%s.", $alias);
        $fields['age'] = sprintf('age(%s"birth_date")', $alias);

        return $fields;
    }
```

Custom queries
--------------

If now, we want to display the name of the employee's department instead of the `department_id`, using `findByPk()` is not enough. We have to create a joint query to bring this information back. Let's create a method in our model class which returns an employee with department informations. We can immediately write the following query (The [NATURAL JOIN](http://www.PostgreSQL.org/docs/8.4/static/queries-table-expressions.html "documentation PostgreSQL") creates joints on fields in common).

```sql
SELECT *, dept.name FROM employee NATURAL JOIN department dept WHERE employee_id = ?
```

However, this query using this form has several drawbacks:

 * It does not use the projection method `getSelectFields()` and will not return the `age` field ;
 * hardcodig the table names can be a hassle if our schema changes.

Pomm Map classes do propose methods to dynamically get those informations: Ideally, our query could be seen like this:

```sql
SELECT %A, dept.name FROM %B NATURAL JOIN %C WHERE employee_id = ?
```

 * %A is the list of B table fields ;
 * %B is the employees tables ;
 * %C is the departments tables.

B and C are easily replaced using the `getTableName()` method given by each Map class. We do know we can get the list of columns to fetch with the `getSelectFields()` method but it returns an associative array with the key being the field alias and the value being… its value. It is necessary to format this array into a list of fields. Map classes do propose dedicated methods: [the formatters](http://pomm.coolkeums.org/documentation/manual-1.1#fields-formatters).

 * `formatFields(methode, alias)` ;
 * `formatFieldsWithAlias(methode, alias)`.

```php
$this->formatFields('getSelectFields', 'pika');
// "pika.employee_id", "pika.first_name", "pika.last_name", "pika. ....
$this->formatFieldsWithAlias('getSelectFields', 'plop');
// "plop.employee_id" AS "employee_id", "plop.first_name" AS "first_name", ...
```

It is now easy to focus on what the query does:

```php
<?php // lib/ElCaro/Company/EmployeeMap.php
// [...]

    public function initialize()
    {
// [...]
        $this->addVirtualField('department_name', 'varchar');
    }

    public function getEmployeeWithDepartment($employee_id)
    {
        $department_map = $this->connection->getMapFor('\ElCaro\Company\Department');
        $sql = <<<SQL
SELECT
  :employee_fields_emp, dept.name AS department_name
FROM
  :employee_table emp
    NATURAL JOIN :department_table dept
WHERE
    emp.employee_id = ?
SQL;

        $sql = strtr($sql, array(
            ':employee_fields_emp' => $this->formatFieldsWithAlias('getSelectFields', 'emp'),
            ':employee_table' => $this->getTableName(),
            ':department_table' => $department_map->getTableName()
        ));

        return $this->query($sql, array($employee_id))->current();
    }
```
Replace the use of `findByPk()` by this method in our controller:

```php
if (!$employee = $connection
    ->getMapFor('\ElCaro\Company\Employee')
    ->getEmployeeWithDepartment($_GET['employee_id']))
{
    printf("No such user.");
    exit;
}
```

And in the corresponding template:

```php
      <li>Department: <?php echo $employee["department_name"] ?>.</li>
```

Being able to perform custom SQL queries from map classes is a very powerful functionality as it allow developers to leverage Postgressql features. By example, we know that departments are structured as a tree. We can ask PostgreSQL to bring back in an array all the departments an employee belongs to. In order to do that, we can use a SQL recursive query with an array aggregation:

```php
<?php // lib/ElCaro/Company/EmployeeMap.php
// [...]

    public function initialize()
    {
        parent::initialize();
        $this->addVirtualField('age', 'interval');
        $this->addVirtualField('department_names', 'varchar[]');
    }

    public function getEmployeeWithDepartment($employee_id)
    {
        $department_map = $this->connection->getMapFor('\ElCaro\Company\Department');
        $sql = <<<SQL
WITH RECURSIVE
  depts  (department_id, name, parent_id) AS (
      SELECT :department_fields_alias_d FROM :department_table d NATURAL JOIN :employee_table emp WHERE emp.employee_id = ?
    UNION ALL
      SELECT :department_fields_alias_d FROM depts parent JOIN :department_table d ON parent.parent_id = d.department_id
  )
SELECT
  :employee_fields_alias_emp, array_agg(depts.name) AS department_names
FROM
  :employee_table emp,
  depts
WHERE
    emp.employee_id = ?
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
```

And in the template:

```php
      <li>Departments: <?php echo join(' &gt; ', $employee["department_names"]) ?>.</li>
```

The query above uses the SQL `WITH` clause which creates named sets and call them back. It acts like a sub select. The first set named `depts` is the recursive part. It has a starting set which is the employee's direct department added to the result of the recursive term which brings all the parents until it is no more possible. The `depts` set contains the employee's all departments. The final query is a simple `CROSS JOIN` between an employee and these departments names aggregated as an array.

Object oriented queries
-----------------------

Even though if the previous query is a good thing, what if we want to create a link on each department that would point to its profile ? We hence need to get the `department_id` alongside with the name. We would create a new field that aggregate ids but it would not be really handy.

PostgreSQL does propose a very interesting feature: when you do create a table, it automatically creates the according matching composite type. This means the type `company.department` does exist and you can use it in your queries:

    elcaro$> SELECT department FROM department;
    ┌─────────────────────────────┐
    │         department          │
    ├─────────────────────────────┤
    │ (1,"el caro corp.",)        │
    │ (2,siège,1)                 │
    │ (3,direction,2)             │
    │ (4,comptabilité,1)          │
    │ (5,"direction technique",3) │
    │ (6,"hotline niveau II",5)   │
    │ (7,"datacenter skynet",1)   │
    │ (8,"technique & réseau",7)  │
    │ (9,"Hotline niveau I",7)    │
    │ (10,Direction,7)            │
    └─────────────────────────────┘
    (10 rows)

The result above has **only one column** with type `department`. What we pompously call "object oriented queries" is just the use of these composite types like they were values. This is really powerful since we just have to change the following line to aggregate directly departments objects:

```sql
...
SELECT
  %s, array_agg(depts) AS departments
FROM
  %s emp,
  depts
...
```

The `departments` column now contains an array of `department` entities. We have to change the template to display the line correctly:

```php
      <li>Departments: <?php echo join(' &gt; ', array_map(function($dept) {
          return sprintf(
              '<a href="/show_department.php?department_id=%d">%s</a>', 
              $dept["department_id"],
              $dept["name"]); 
      }, $employee["departments"])) ?>.</li>
```

But this is not enough: PHP will complain the argument passed to `array_map` is not an array and it will be right. As we did not declared field `departments` in the `Employee` class, Pomm will just cast the value as a string. We have to register the converter system that the `departments` virtual field contains an array of `department` type. The problem is our converter does not know how to translate this kind of data. We have to register a new converter to handle this data type in the `bootstrap.php`:

```php
<?php // bootstrap.php

$loader = require __DIR__."/vendor/autoload.php";
$loader->add(null, __DIR__."/lib");

$database = new Pomm\Connection\Database(array('dsn' => 'pgsql://greg/greg', 'name' => 'el_caro'));
$database->registerConverter(
    'Department', 
    new \Pomm\Converter\PgEntity($database->getConnection()->getMapFor('\ElCaro\Company\Department')), 
    array('company.department')
    );

return $database->getConnection();
```

Here we do register a new converter called `Department` which associate the Postgres type `company.department` to converter instance `PgEntity`. Let's now use this converter in the virtual field `departments`:

```php
<?php // lib/ElCaro/Company/EmployeeMap.php
// [...]

class EmployeeMap extends BaseEmployeeMap
{
    public function initialize()
    {
        parent::initialize();
        $this->addVirtualField('age', 'interval');
        $this->addVirtualField('departments', 'company.department[]');
    }
```

![Alt text](department.png "Department tree")

The `show_department.php` is left as an exercise :)

Writing in the database
-----------------------

Let's now imagine the interface `show_employee.php` makes user able to change the 'manager / worker' for each employee by clicking on it. We modify the template to create the link:

```php
      <li>Status: <a href="employee_change_status.php?status=<?php echo $employee['is_manager'] ? 1 : 0 ?>&employee_id=<?php echo $employee["employee_id"] ?>"><?php echo $employee["is_manager"] ? "manager" : "worker" ?></a>.</li>
```

ORM users would probably write the controller this way:

 1. I fetch the employee from its id.
 2. If it does not exist I send an error notification back.
 3. Otherwise I update the record.
 4. I send the response back.

PostgreSQL can do almost all of that in one move. Let's see a structure of an UPDATE statement:

```sql
UPDATE :table SET :field1 = :value1, [:fieldN = :valueN] WHERE :clause_where RETURNING :list_field
```

This means in doing this update, if the provided is exists, it is updated and the values from the database are returned back to the entity. This what the method `updateByPk()` does:

```php
<?php // employee_change_status.php

$connection = require(__DIR__."/bootstrap.php");

// CONTROLLER
if (!$employee = $connection->getMapFor('\ElCaro\Company\Employee')
    ->updateByPk(array('employee_id' => $_GET['employee_id']), array("is_manager" => $_GET["status"] == 0)))
{
    printf("No such employee !"); exit;
}

header(sprintf("Location: show_employee.php?employee_id=%d", $employee["employee_id"]));
```

This method does only update a single employee on a defined number of attributes. It is also possible to save / update entities trough the `saveOne()` method without worrying if it is an update or an insert. All these methods does update the entities with the values from the database. More about this can be read in [Pomm'sdocumentation](pomm.coolkeums.org/documentation/manual-1.1 "Pomm's documentation").

Conlusion
----------

During this tutorial, we just scratched the surface of the possibilities offered by PostgreSQL. We could continue and leverage HStore key -> value store, create hierarchical tags using materialized path (LTree), version database change creating a table with a `company.employee` column type. There would be so much more to say.

Pomm is a tool which aims at bringing PostgreSQL powerful features to developers fingertips. The line between Pomm and ORM may look very thin but the underlying philosophy is utterly different. Pomm acts as a specialized tool, it is efficient and can speed up your development. In leveraging PostgreSQL unique features, Pomm opens interesting perspectives one could not easily reach either by using PDO nor ORM.

You can find this tutorial's [github repository online](https://github.com/chanmix51/elcaro "ElCaro"). I want to thanks [Julien Bianchi](https://github.com/jubianchi "Julien BIANCHI") who went up to package a VM Vagrant / VirtualBox for this tutorial. You can find this in his [Github repository](https://github.com/jubianchi/elcaro/tree/vagrant). Also many thanks to [Nicolas Joseph](https://github.com/sanpii "Nicolas Joseph") for his very efficient help on this article.


