Pomm - Postgresql / PHP Object Model Manager
============================================

Qu'est ce que c'est ?
---------------------

Pomm est un **gestionnaire de modèle objet** dédié au moteur de base de données Postgresql. Qu'est ce qu'un gestionnaire de modèle objet ?

C'est avant tout un **hydrateur** d'objets qui utilise un convertisseur entre PHP et Postgresql pour assurer qu'un booléen dans Postgres sera vu depuis PHP comme tel, de même pour les tableaux, le type clé -> valeur 'HStore', les types géométriques, XML, JSON etc.

Cette fonctionnalité de conversion est très importante car le typage dans Postgresql est un élément incontournable de la définition du schéma par contrainte. La possibilité d'enrichir Postgresql avec nos propres types est prise en compte et nous verrons comment bénéficier des fonctionnalités "orienté objet" de Postgesql par ce biais.

C'est également un gestionnaire de modèle orienté objet car Pomm crée des classes de mapping qui lie des structures SQL avec des objets PHP. Nous verrons là encore les grosses différences entre Pomm et les ORMs classiques et comment utiliser la puissance du SQL de Postgres au service d'une petite application. 

En quoi Pomm est il différent d'un ORM et pourquoi l'utiliser ?
---------------------------------------------------------------

Il est difficile de répondre rapidement à cette question sans tomber dans l'ornière du débat pro / anti ORMs. L'auteur développe avec PHP et Postgresql depuis plus d'une dizaine d'année. L'avènement des ORMs a certes changé la façon d'utiliser les bases de données en apportant des vraies couches modèles au sein du MVC mais ils ont apporté aussi un certain nombre d'inconvénients très handicapants pour les habitués des fonctionnalités des bases de données et de Postgresql. Pomm part donc du parti pris de ne fonctionner qu'avec Postgresql et son objectif est de permettre aux développeurs PHP de tirer parti de ses fonctionnalités. 

Un des plus gros problèmes des ORMs est qu'en calquant une logique orientée objet sur des structures SQL, ils figent ces dernières suivant la définition de classes PHP (ou autres) alors que par définition un ensemble (set) de base de données est extensible. Nous verrons comment Pomm tirer parti de la souplesse de PHP pour créer des objets élastiques s'adaptant à notre besoin. 

Un autre des problèmes des ORMs est lié à la couche d'abstraction: ils proposent un langage pseudo SQL orienté objet et il est souvent délicat de trouver comment faire quelque chose qu'on sait déjà faire en SQL classique. Nous verrons comment Pomm permet de faire directement des requêtes SQL sans les inconvénients de la construction fastidieuse -- que probablement certains d'entre vous ont connu -- qui menait à des scripts peu maintenables et peu testables.

Le présent article vous propose de créer une application en mode texte qui cherche et affiche des informations sur les employés de la société El-Caro Corporation.

Mise en place de l'application
------------------------------

L'application suivante n'utilise pas de framework et est volontairement minimaliste. Il est bien sûr forement consillé d'en utiliser un, il existe à ce propos un adaptateur pour [Silex et Symfony](http://pomm.coolkeums.org/download). Ne vous étonnez donc pas de ne pas trouver de belles URL (routing), de contrôleurs encapsulés (et testables), de moteur de template (fort utile) et autres bonnes pratiques, cela va nous permettre de nous concentrer sur le sujet de cet article.

Nous allons utliser [Composer](http://composer.org "composer c'est le bien") pour installer Pomm et instancier un auto-loading dans notre projet. Pour cela, il n'est pas utile de créer plus qu'un fichier `composer.json` comme suit dans un répertoire vierge :

```json
{
    "minimum-stability": "dev",
    "require": {
        "pomm/pomm": "dev-master"
    }
}
```

Reste à appeler le script `composer.phar install` pour que composer installe Pomm et le prenne en compte dans son autoloader.


Bienvenue dans la société El-Caro Corp.
---------------------------------------

Ce tutoriel vous propose de créer une application simpliste de gestion des salariés de la société informatique « El-Caro Corporation ». Cette société est divisée en départements hiérarchisés et chaque employé appartient à un département. La structure de la base de données est la suivante :

![Alt text](elcaro.db.png "Database Structure")

Nous allons créer un schéma nommé `company` dans notre base de données pour y créer la structure décrite ci dessus :

```sql
el-caro$> CREATE SCHEMA company;
el-caro$> SET search_path TO company, public;
el-caro$> SHOW search_path;
company, public
```

La commande `SHOW` doit nous retourner `company, public` signe que le client Postgres va d'abord chercher les objets par défaut dans le schéma `company` puis ensuite dans le schéma par défaut `public`. Une fois cela fait, implémentons la structure :

    elcaro$> CREATE TABLE department (
        department_id       serial      PRIMARY KEY,
        name                varchar     NOT NULL,
        parent_id           integer     REFERENCES department (department_id)
        );

Tel que nous l'avons décrite, cette table possède un identifiant technique -- un entier -- qui s'auto incrémente à l'aide d'une séquence qui est auto générée et initialisée à la création de la table comme l'indique Postgresql. Notons que le `parent_id` même s'il est indiqué comme référent au département parent peut être nul dans le cas du département père. En revanche la contrainte de clé étrangère forcera tout département indiqué comme père à exister au préalable dans la table.

```sql
el-caro$> CREATE TABLE employee (
    employee_id         serial          PRIMARY KEY,
    first_name          varchar         NOT NULL,
    last_name           varchar         NOT NULL,
    birth_date          date            NOT NULL CHECK (age(birth_date) >= '18 years'::interval),
    is_manager          boolean         NOT NULL DEFAULT false,
    day_salary          numeric(7,2)    NOT NULL,
    department_id       integer         NOT NULL REFERENCES department (department_id)
    );
```

Nous voyons ici que la structure d'un empoyé est fortement contrainte. Une vérification -- pour l'exemple -- d'age est faite pour vérifier que la date de naissance entrée ne corresponde pas à un mineur. Dans le cas d'un employé, l'appartenance à un département est rendue obligatoire par la contrainte `NOT NULL` sur le champs de clé étrangère `department_id` vers la table `department`.

Un jeu de données est disponible dans [ce Gist](https://gist.github.com/raw/4664191/c1fbaba2c82b4d2950709ec2c208852894d16152/structure.sql [wget me]).

Génération du modèle PHP
------------------------

À partir de ce modèle de base de données, Pomm va construire les classes qui correspondent aux structures des tables pour nous permettre de nous affranchir des traitements fastidieux de PDO. Dans un premier temps, nous créons un fichier appelé `bootstrap.php` qui sera appelé par nos scripts et dont le but est d'initialiser la base de données.

```php
<?php // bootstrap.php

$loader = require __DIR__."/vendor/autoload.php";
$loader->add(null, __DIR__."/lib");

$database = new Pomm\Connection\Database(array('dsn' => 'pgsql://greg/greg', 'name' => 'el_caro'));

return $database->getConnection();
```


Notez que nous spécifions le répertoire `lib` comme répertoire par défaut pour trouver les namespaces à l'autoloader. 

Pour maintenant générer les fichiers de mapping, créons le fichier `generate_model.php` dont une version plus générale est disponible dans [ce Gist](https://gist.github.com/1510801#file-generate_model-php "generate_model.php").

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

Ce script utilise un des outils fournis avec Pomm: [le scanner de schémas](http://pomm.coolkeums.org/documentation/manual-1.1#map-generation-tools [documentation]). Cet outil utilise l'inspecteur de base de données de Pomm pour générer des classes de mapping liées aux tructures stockées en base. Dans le cas présent, nous lui demandons de scanner le schéma `company` et de générer les fichiers dans le sous répertoire `lib`, là où nous avons fait pointer l'auto-loader par défaut dans le fichier `bootstrap.php`. Un appel à ce script va nous générer la structure de fichiers suivante ;

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

Cette architecture ne choquera pas les utilisateurs habitués à utiliser des ORMs. Nous pouvons constater que le namespace utilisé par les classes de modèle est `\ElCaro\Company` c'est à dire le nom de la base de donnée passé en paramètre lors de l'instanciation de la classe `Database` avec le nom du schéma. Ainsi, il est possible d'avoir plusieurs classes de tables portant le même nom mais déclarées dans des schémas Postgresql différents. D'autre part, chaque table génère 3 classes :

 * Une classe portant le même nom que la table à la casse près
 * Une classe portant le même nom mais affublé du suffix `Map`
 * La même classe dans le sous namespace `Base`.

Les classes du sous namespace `Base` contiennent la définition déduite depuis la structure de la base de données. Ces fichiers seront écrasés à chaque introspection en cas d'évolution de la structure de la base, il serait donc malvenu qu'elle contiennent du code que nous aurions pu placer là. C'est pour cela que la classe `Map` hérite de sa consoeur dans `Base`. Vous pouvez y placer votre code, cette classe ne sera pas écrasée. 

Les utilisateurs d'ORMs ne seront pas non plus surpris d'apprendre que la classe Map est l'outil qui s'occupera de gérer la vie leur entité correspondante avec la base de données, à savoir :

 * `DepartmentMap` sauvegarde, génère et renvoie des collections d'entités `Department`.
 * `EmployeeMap` renvoie des collections d'entités `Employee`.

Premiers pas
------------

Pour notre première interface, nous allons afficher la liste des départements avec un lien sur chacun d'entre eux pour permettre d'afficher la liste des employés qui leur sont directement attachés. Créons le fichier index.php avec le code PHP suivant:

```php
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
      <li><a href="/list_empoyee.php?department_id=<?php echo $department["department_id"] ?>"><?php $department["name"] ?></a></li>
  <?php endforeach ?>
    </ul>
<?php else: ?>
    <p>No departments !?!? There must be a bug somewhere...</p>
<?php endif ?>
  </body>
</html>
```

Commentons le code ci dessus :

 1. La connexion nous permet d'instancier les classes Map
 2. La classe Map sait faire des requêtes qui ramènent des collections d'entités correspondantes.
 3. Ces collections sont accessibles via foreach et retournent leurs entités
 4. Les valeurs internes des entités sont accessibles via la notation de tableau.

Comment faire dans ces conditions pour surcharger nos entités ? Tout bonnement comme n'importe quelle entité. Si nous souhaitons par exemple afficher le nom des départements en minuscules commençant par une majuscule, il suffit de créer la méthode `getName()` dans la classe `Department` :

```php
<?php //lib/ElCaro/Company/Department.php

namespace ElCaro\Company;

use \Pomm\Object\BaseObject;
use \Pomm\Exception\Exception;

class Department extends BaseObject
{
    public function getName()
    {
        return ucwords($this->get('name'));
    }
}
```

L'appel à `$department['name']` utilisera alors cette surcharge. Il est bien évidemment possible d'appeler directement les accesseurs `$department->getId()` fonctionnera tout comme `$department['id']`.


