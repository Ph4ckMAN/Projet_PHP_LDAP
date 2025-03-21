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

L'utilisateur **admin** possède plusieurs fonctionnalités.

On se connecte et on arrive sur cette page : 
![adminPanel](https://github.com/user-attachments/assets/d7b83268-faf3-4ca7-9661-fa9cbabe518c)

Ici sont affchés tous les utilisateurs de la base LDAP.

On peut voir plusieurs fonctionnalités.

#### 3.1.1 Filtre de recherche


Pour l'utilisateur Admin
Filtre de recherche 
Ajout d'un utilisateur 
Suppréssion d'un utilisateur
Modification d'un numéro de téléphone

Pour un utilisateur de la base 
Possibilité de conexion 
Affichage de ses propres informations 

