Pomm - Postgresql / PHP Object Model Manager
============================================

Qu'est ce que c'est
-------------------

Pomm est un **gestionnaire de modèle objet** dédié au moteur de base de données Postgresql. Qu'est ce qu'un gestionnaire de modèle objet ?

C'est avant tout un **hydrateur** d'objets qui utilise un convertisseur entre PHP et Postgesql pour assurer qu'un booléen dans Postgres sera vu depuis PHP comme tel, de même pour les tableaux, le type clé -> valeur 'HStore', les types géométriques, XML, JSON etc.

Cette fonctionnalité de conversion est très importante car le typage dans Postgresql est un élément incontournable de la définition du schéma par contrainte. La possibilité d'enrichir Postgresql avec nos propres types est prise en compte et nous verrons comment bénéficier des fonctionnalités "orienté objet" de Postgesql par ce biais.

C'est également un gestionnaire de modèle orienté objet car Pomm crée des classes de mapping qui lie des structures SQL avec des objets PHP. Nous verrons là encore les grosses différences entre Pomm et les ORMs classiques et comment utiliser la puissance du SQL de Postgres au service d'une petite application. 

Le présent article vous propose de créer une application en mode texte qui cherche et affiche des informations sur les employés de la société ELCARO Corporation.

Mise en place de l'application
------------------------------

Nous allons utliser [Composer](http://composer.org "composer c'est le bien") pour installer Pomm et instancier un auto-loading dans notre projet. Pour cela, il n'est pas utile de créer plus qu'un fichier `composer.json` comme suit dans un répertoire vierge :


    {
        "minimum-stability": "dev",
        "require": {
            "pomm/pomm": "dev-master"
        }
    }

Reste à appeler le script `composer.phar install` pour que composer installe Pomm et le prenne en compte dans son autoloader.


Bienvenue dans la société ELCARO Corp.
--------------------------------------

Ce tutoriel vous propose de créer une application simpliste de gestion des salariés de la société informatique « ELCARO Corporation ». Cette société est divisée en départements hiérarchisés et chaque employé appartient à un département. La structure de la base de données est la suivante :

![Alt text](elcaro.db.png "Database Structure")

Nous allons créer un schéma nommé `company` dans notre base de données pour y créer la structure décrite ci dessus :

    elcaro$> CREATE SCHEMA company;
    elcaro$> SET search_path TO company, public;
    elcaro$> SHOW search_path;
    company, public

La commande `SHOW` doit nous retourner `company, public` signe que le client Postgres va d'abord chercher les objets par défaut dans le schéma `company` puis ensuite dans le schéma par défaut `public`. Une fois cela fait, implémentons la structure :

    elcaro$> CREATE TABLE department (
        department_id       serial      PRIMARY KEY,
        name                varchar     NOT NULL,
        parent_id           integer     REFERENCES department (department_id)
        );

Tel que nous l'avons décrite, cette table possède un identifiant technique -- un entier -- qui s'auto incrémente à l'aide d'une séquence qui est auto générée et initialisée à la création de la table comme l'indique Postgresql. Notons qie le `parent_id` même s'il est indiqué comme référent au département parent peut être nul dans le cas du département père. En revanche la contrainte de clé étrangère forcera tout département indiqué comme père à existant au préalable dans la table.

    elcaro$> CREATE TABLE employee (
        employee_id         serial          PRIMARY KEY,
        first_name          varchar         NOT NULL,
        last_name           varchar         NOT NULL,
        birth_date          date            NOT NULL CHECK (age(birth_date) >= '18 years'::interval),
        is_manager          boolean         NOT NULL DEFAULT false,
        day_salary          numeric(7,2)    NOT NULL,
        department_id       integer         NOT NULL REFERENCES department (department_id)
        );

Nous voyons ici que la structure d'un empoyé est fortement contrainte. Une vérification -- pour l'exemple -- d'age est faite pour vérifier que la date de naissance entrée ne corresponde pas à un mineur. Dans le cas d'un employé, l'appartenance à un département est rendue obligatoire par la contrainte `NOT NULL` sur le champs de clé étrangère `department_id` vers la table `department`.

Génération du modèle PHP
------------------------

À partir de ce modèle de base de données, Pomm va construire les classes qui correspondent aux structures des tables pour nous permettre de nous affranchir des traitements fastidieux de PDO. Dans un premier temps, nous créons un fichier appelé `bootstrap.php` qui sera appelé par nos scripts et dont le but est d'initialiser la base de données.

    <?php

    $loader = require __DIR__."/vendor/autoload.php";
    $loader->add(null, __DIR__."/lib");

    $database = new Pomm\Connection\Database(array('dsn' => 'pgsql://greg/greg', 'name' => 'elcaro'));

    return $database->getConnection();



Pour cela, créons le fichier `generate_model.php` dont la source est disponible dans [ce Gist](https://gist.github.com/1510801#file-generate_model-php "generate_model.php").
