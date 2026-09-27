YEBOOX DESKTOP 9.0.0 — WINDOWS 10/11 64 BITS
================================================

INSTALLATION COMPLÈTE, HORS LIGNE
1. Exécutez « Yeboox Desktop-9.0.0-Setup-x64.exe ».
2. Choisissez votre dossier d’installation.
3. L’assistant installe PHP, SQLite, les extensions et les DLL Visual C++ x64.
4. Un contrôle exécute PHP et une lecture/écriture SQLite en mémoire avant de terminer.
5. Démarrez Yeboox : découvrez les cinq slides puis créez votre administrateur.

Aucun PHP, serveur PostgreSQL ou serveur MySQL séparé n’est nécessaire.
Les composants système de Windows 10/11 doivent être à jour.
Le setup inclut les DLL applicatives nécessaires ; il ne remplace pas les DLL
système de Windows. Un antivirus ou une installation Windows endommagée peut
toujours empêcher l’exécution. Aucun certificat éditeur n’est configuré.

PREMIÈRE UTILISATION
Les cinq écrans présentent le commerce, la caisse, le catalogue et les stocks,
les clients et rendez-vous, puis les rapports, la comptabilité et les accès.
Les illustrations ne constituent pas des données de démonstration.
La base métier est vide : aucun utilisateur, client, produit ou vente prédéfini.
Les rôles, moyens de paiement et structures techniques sont initialisés.
Vous choisissez le nom, l’adresse e-mail et le mot de passe de l’administrateur.

DONNÉES LOCALES ET MISES À JOUR
Base SQLite : %APPDATA%\Yeboox Desktop\database\database.sqlite
Clé applicative : %APPDATA%\Yeboox Desktop\application-key.txt
Journaux : %APPDATA%\Yeboox Desktop\storage\logs

Les données ne sont pas enregistrées dans Program Files et ne sont pas
remplacées lors d’une mise à jour. Une sauvegarde SQLite est créée avant
les migrations de chaque version. Conservez la clé applicative avec vos sauvegardes.
En cas de corruption, l’application s’arrête sans remplacer la base par une base vide.

PASSAGE DE LA VERSION 8.13 POSTGRESQL
9.0 utilise SQLite et une monnaie comptable unique choisie au premier démarrage. Les anciennes données PostgreSQL ne sont ni supprimées ni importées.
Une nouvelle base SQLite vide est créée s’il n’en existe pas déjà sur le poste.
Pour transférer des données PostgreSQL existantes, prévoir une migration dédiée.
Une ancienne base SQLite existante est conservée ; l’accueil n’est présenté
que tant qu’aucun utilisateur n’a été créé.

PRIX
Les corrections de 8.13 sont conservées : précision interne des conversions
USD/CDF, prix du catalogue repris en caisse. La correspondance des prix s’entend
à taux de change et mode fiscal identiques (prix TTC ou HT).

MODE PORTABLE
Les données se trouvent dans « Yeboox POS Data » à côté de l’exécutable.
