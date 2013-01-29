Pomm - Postgresql / PHP Object Model Manager
============================================

Qu'est ce que c'est
-------------------

Pomm est un **gestionnaire de modèle objet** dédié au moteur de base de données Postgresql. Qu'est ce qu'un gestionnaire de modèle objet ?

C'est avant tout un **hydrateur** d'objets qui utilise un convertisseur entre PHP et Postgesql pour assurer qu'un booléen dans Postgres sera vu depuis PHP comme tel, de même pour les tableaux, le type clé -> valeur 'HStore', les types géométriques, XML, JSON etc.

Cette fonctionnalité de conversion est très importante car le typage dans Postgresql est un élément incontournable de la définition du schéma par contrainte. La possibilité d'enrichir Postgresql avec nos propres types est prise en compte et nous verrons comment bénéficier des fonctionnalités "orienté objet" de Postgesql par ce biais.

C'est également un gestionnaire de modèle orienté objet car Pomm crée des classes de mapping qui lie des structures SQL avec des objets PHP. Nous verrons là encore les grosses différences entre Pomm et les ORMs classiques.

Bienvenue dans la société ELCARO Corp.
--------------------------------------

Ce tutoriel vous propose de créer l'application simpliste de gestion des salariés de la société informatique « ELCARO Corporation ». Cette société est divisée en départements hiérarchisés et chaque employé appartient à un département. La structure de la base de données est la suivante :


