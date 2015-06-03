# Quelques astuces de recherche #

_Notes rédigées par Gaëlle Faure, webmestre d'Ascodocpsy._

**Pré-requis pour les astuces suivantes :**
le champ doit être **indexé** et de type **count**

**_Rechercher les notices qui n'ont pas de mot-clé :_**
Dans le champ Descripteur : 2 tirets bas sans espace suivi de empty

**_Rechercher les notices qui ont 1 mot-clé :_**
Dans le champ Descripteur : 2 tirets bas sans espace suivi de has1. 1 peut être remplacé par n'importe quelle valeur

**_Rechercher les notices qui ont au moins 1 valeur :_**
Dans le champ Descripteur : 2 tirets bas sans espace suivi de has étoile

**_Rechercher dans un champ qui n'est pas proposé dans le formulaire :_**
Dans le champ Recherche dans toute la base : NOTES:asco26 par exemple


# Comment indexer un champ ? #
Aller dans : Administration > Schémas de base de données >  ascodocpsy.xml : modifier.

Ajouter un index et/ou cocher la case "Count" qui correspond à l'index. Enregistrer le schéma. Et demander une réindexation automatique de la base.

Un index "count" permet de savoir si le champ est rempli ou pas.

Attention : quand est un indexé est modifié, tous les fichiers du module sont impliqués.


# Factorisation des champs : dans include > templates.html #
TDTA = Type + Date + Titre + Auteur

MTCNR = MotClé + Thème + Candes + Nomp + Résumé

# Comment créer un nouveau type de document ? #

1. Rajouter des champs dans la gestion de bases de données : index, lookup, alias

2. Modifier le formulaire de recherche

3. Penser à public\_type

4. Cinq affichages à modifier : FullRef, Load, List, ShortRef et Search


# Feuille de style d'impression #

La mettre dans Gestion des fichiers de l'application > web > css > ascodocpsy

et modifier Administration > Configuration > general.config pour y spécifier le média print

# Lookup, késako ? #

Il s'agit de la **saisie sur index** : à la recherche et à la saisie.

On peut avoir un index sans avoir de lookup.

Le lookup s'applique à des champs comme Auteur, Revue, ISBN. En revanche, il est inutile pour les champs comme Titre, Résumé, Notes...

# Contrôle des champs dans le formulaire de saisie de notice #

Administration > Gestion des fichiers de l'application > web > js > load.js

Un tableau pour chaque type de document liste tous les contrôles appliqués.