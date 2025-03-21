# Projet_PHP_LDAP

Voici un projet LDAP utilisant Python et PHP. Ce projet consistait à mettre en place un annuaire LDAP pour centraliser la gestion des utilisateurs et des groupes. Nous avons dû développer une application web permettant l’ajout, la modification, la suppression et la recherche des données, avec un contrôle d’accès adapté aux rôles des utilisateurs.

## 1. Installation de slapd

Il faut installer des paquets : ```sudo apt-get install ldap-utils slapd php-ldap```

On reconfigure le paquet <u>slapd</u> : ```sudo dpkg-reconfigure slapd```


*Passer la configuration d'OpenLDAP ?* **non**

*Nom de domaine ?* **iut6-kourou.fr**

*Quelle base de données ?* **hdb**

*Voulez-vous que la base de données soit effacée lorsque
slapd est purgé ?* **oui**

*Supprimer les anciennes bases de données ?* **oui**

*Mot de passe administrateur ?* **MDP ADMIN**

*Confirmer ce mot de passe ?* **MDP ADMIN**

*Autoriser le protocol LDAPv2 ?* **non**

## 2.Génération des fichiers LDIF

### 2.1 Génération des fichier LDIF

On créer un fichier **unitOrg.ldif** :
```
dn: ou=People,dc=iut6-kourou,dc=fr
objectClass: top
objectClass: organizationalUnit
description: Branche People
ou: People*

dn: ou=Groups,dc=iut6-kourou,dc=fr
objectClass: top
objectClass: organizationalUnit
description: Branche Groups
ou: Groups
```
Ce fichier va contenir les organizational Units de notre base LDAP.

### 2.2 Création d’un code python pour générer les fichiers

Le script Python **generate_ldif.py** permet de convertir les données
d’un fichier CSV en trois fichiers principaux :

● **user.ldif** : Contenant les informations des utilisateurs (identifiants, mots de passe hachés, groupe d’appartenance).

● **groupe.ldif** : Crée les groupes et intègre les utilisateurs appartenant au groupe.

● **password.csv** : Liste des identifiants et mots de passe générés aléatoirement.

Le script prend en entrée le fichier ***user.csv***, applique des transformations
(nom normalisé, génération de mots de passe aléatoires), et génère
automatiquement les fichiers de peuplement.

Ce fichier **user.csv** est écrit sous forme : *FORMATION;NOM;Prénom;TÉLÉPHONE*

Par exemple : *BUT3RT;JOHN;Doe;0611111111*

### 2.3 Remplissage de la base

Après avoir obtenu les fichiers .ldif, on remplit les classes avec le Organizational Unit "Groups" : 
```
ldapadd -x -D "cn=admin,dc=iut6-kourou,dc=fr" -W -f unitOrg.ldif
```
On remplit les groupes :
```
ldapmodify -x -H ldap://<IP Serveur LDAP> -D "cn=admin,dc=iut6-kourou,dc=fr" -W -f groupe.ldif
```
On peuple la base avec les utilisateurs : 
```
ldapmodify -x -H ldap://<IP Serveur LDAP> -D "cn=admin,dc=iut6-kourou,dc=fr" -W -f user.ldif
```
## 3. Application web

Cette application web permet à l'utilisateur d'intéragir graphiquement avec la base LDAP.
Tout d'abord il faut mettre l'adresse IP de votre serveur LDAP dans les codes où il y a écrit <u>"IP Serveur LDAP"</u>
L'interface de connexion se présente comme ceci : 

![connexion](https://github.com/user-attachments/assets/7279bc84-6f87-4a86-a009-b64d705730f2)

### 3.1 Administrateur

L'administrateur possède plusieurs fonctionnalités.
On se connecte et on arrive sur cette page :

![adminPanel](https://github.com/user-attachments/assets/d7b83268-faf3-4ca7-9661-fa9cbabe518c)

Ici sont affchés tous les utilisateurs de la base LDAP.
On peut voir plusieurs fonctionnalités.

#### 3.1.1 Filtre de recherche

L'administrateur a accès à un **filtre de recherche**. Ce filtre permet de rechercher des utilisateurs de la base :

![filtreRecherche](https://github.com/user-attachments/assets/8c36e2ec-aef0-4499-9ffd-c54b092b3382)

#### 3.1.2 Bouton Modifier

![bouton_modifier](https://github.com/user-attachments/assets/321698a6-c8af-4ce3-bec9-fe0787b88190)

On a le bouton **Modifier** qui permet à l'administrateur de modifier le numéro de téléphone d'un utilisateur de la base.

![modifier1](https://github.com/user-attachments/assets/639cf3fa-d6cb-4248-b7a9-333399a3faeb)

L'admin doit écrire 10 chiffres (pas plus, pas moins) puis clique sur le bouton **Modifier** pour mettre à jour la modification.

Une fois la modification faite, on a un message qui nous confirme la modification : 

![modifier2](https://github.com/user-attachments/assets/c7eadba0-a4f2-4abf-b9d4-69236600c044)

On peut voir que le numéro de l'utilisateur a bien été mis à jour : 

![modifier3](https://github.com/user-attachments/assets/51c00356-e622-4431-a973-5628d4369d8f)

#### 3.1.3 Bouton Supprimer

![bouton_supp](https://github.com/user-attachments/assets/3073fd97-02df-4b27-beba-6dd080183447)

On a le bouton **Supprimer** qui permet à l'administrateur de supprimer un utilisateur de la base. Une fois l'utilisateur supprimé, un message de confirmation apparaît :

![supprimer](https://github.com/user-attachments/assets/701a8365-ef31-421e-905e-324bbe9f3658)

#### 3.1.4 Bouton Ajouter

![bouton_aj](https://github.com/user-attachments/assets/019318ac-a042-405f-ae27-99b6223f98f1)

L'administrateur possède un bouton **Ajouter** qui permet d'ajouter un utilisateur à la base LDAP.
En cliquant sur le bouton, on est redirigé vers cette page : 

![ajouter](https://github.com/user-attachments/assets/331b5f99-59ef-47e2-b8e1-f16528cbbbe0)

On peut créer un utilisateur : 

![ajouter2](https://github.com/user-attachments/assets/7218f32d-a287-4aa0-b822-957f22dc6305)

Une fois l'utilisateur ajouté, on obtient un message de confirmation : 

![ajouter3](https://github.com/user-attachments/assets/d495f8cc-4fcc-4b41-80b6-a081574a3211)

On peut désormais vérifier que l'utilisateur a bien été ajouté à la base : 

![ajouter4](https://github.com/user-attachments/assets/5652f51a-f2f8-4d4c-9dd5-4bad0fce6ae3)


### 3.2 Utilisateur de la base

Cette application web permet à un utilisateur de la base de se connecter pour voir ses informations. Par exemple avec l'utilisateur que l'on vient de créer : 

![utilisateur1](https://github.com/user-attachments/assets/ac06ee29-1528-4819-8ab1-c545e9e96634)

L'utilisateur connecté peut donc voir ses informations : 

![utilisateur2](https://github.com/user-attachments/assets/bde07360-e35a-4d4b-b12a-01fa5b152356)

L'utilisateur de la base n'a aucun accès aux informations des autres utilisateurs ni aux fonctionnalités de l'administrateur.

### 3.3 Utilisateur Anonymous

Sur cette application web, on a aussi la possibilité de se connecter en tant que anonyme : 

![bouton_anonyme](https://github.com/user-attachments/assets/019286ee-6313-431d-835a-60a98554a93a)

Lorsque l'on se connecte en **Anonymous**, on atteri sur cette page : 

![anonymous](https://github.com/user-attachments/assets/8b80e83f-bc0d-4633-8f09-4c2b27db77d1)

On peut voir que la liste des utilisateurs de la base LDAP est accessible, mais aucun droit d'administrateur n'est attribué. L'utilisateur **anonymous** ne dispose que d'un accès en lecture à la liste des utilisateurs de la base et ne peut effectuer aucune action.
