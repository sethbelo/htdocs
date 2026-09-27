# Yeboox POS 1.4.6 — Windows 10/11 x64

Application de gestion commerciale et de point de vente par Yetoo Tech.

## Installation

1. Fermez toute ancienne instance de Yeboox POS.
2. Lancez `Yeboox POS-1.4.6-Setup-x64.exe` après compilation de cette version.
3. Choisissez le dossier d'installation et terminez l'assistant.
4. Au premier démarrage, créez le compte administrateur demandé par l'application.

La version `Portable` peut être lancée sans installation. Les données utilisateur restent enregistrées séparément de l'exécutable afin qu'une mise à jour ne remplace pas la base locale.

## Base de données locale

- SQLite est intégré : aucun serveur MySQL ou PHP externe n'est nécessaire.
- Une nouvelle installation démarre avec une base prête à être initialisée.
- Sous Windows, les données et sauvegardes se trouvent dans le dossier utilisateur de Yeboox POS.
- Conservez une copie de sauvegarde avant toute opération de maintenance ou changement de poste.

## Nouveautés 1.4.6

- correction de l'impression thermique qui pouvait produire une première page blanche ;
- document d'impression du ticket isolé du reste de l'interface POS ;
- formats thermiques 58, 76 et 80 mm, impression à 300 DPI ;
- nouveau module **Ventes** avec filtres par date et catégorie ;
- chiffre d'affaires HT/TTC, TVA, coût des ventes, bénéfice brut et marge ;
- regroupement du chiffre d'affaires par catégorie ;
- export PDF A4 paysage avec détail des produits, en-tête, pied de page et pagination ;
- runtime PHP, SQLite, GD et Dompdf intégrés au paquet Windows ;
- fonctionnement sans connexion Internet : polices, JavaScript, Tailwind, Livewire et Flux sont servis localement ;
- état **Libre** ou **Occupée** plus visible dans le plan de salle ;
- choix CDF/USD disponible dans les topbars ;
- impression automatique fiable et unique après validation d’un encaissement ;
- adaptation responsive renforcée pour petits écrans, tablettes et ordinateurs.

## Imprimante de tickets

Dans **Configuration → Tickets & PDF**, sélectionnez le modèle et la largeur correspondant au rouleau de l'imprimante. Vérifiez également dans Windows que la même largeur de papier est configurée dans le pilote. L'impression automatique utilise l'imprimante par défaut de Windows.

## API Android et appareils externes

L’API sécurisée et versionnée est disponible sous `/api/v1`. Le guide d’intégration, les règles d’idempotence et l’exemple Retrofit sont documentés dans [`docs/api/README.md`](docs/api/README.md). Le contrat OpenAPI importable se trouve dans [`docs/api/openapi.yaml`](docs/api/openapi.yaml).

## Mise à jour et assistance

L'installation d'une nouvelle version ne doit pas supprimer la base SQLite utilisateur. En cas de problème, sauvegardez le dossier de données avant toute réinstallation et transmettez le journal `electron-startup.log` au support Yetoo Tech.

Copyright © 2026 Yetoo Tech.
