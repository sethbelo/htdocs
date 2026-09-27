# Refonte Yeboox — feuille de route en 3 étapes

## 1. Fondation Livewire et design system — réalisée

- Unification des layouts de l’application et du point de vente.
- Création de la bibliothèque locale `resources/views/components/ui` : boutons, cartes, champs, listes, badges, modales et états vides.
- Suppression de DaisyUI et des dépendances UI chargées par CDN.
- Navigation responsive, thème sombre, états focus et composants tactiles cohérents.

## 2. Reconstruction du POS — réalisée

- Nouveau catalogue tactile responsive avec recherche, filtres, grille/liste et rendez-vous.
- Multi-commandes, panier, tables, bon cuisine, client rapide et fidélité.
- Encaissement par moyens dynamiques, remise bornée, montant reçu et monnaie rendue.
- Reçu thermique, clôture de caisse et synthèse des règlements.
- Stock et prix revérifiés sous verrou transactionnel au moment du paiement.

## 3. Migration fonctionnelle complète — fondation appliquée, écrans à migrer progressivement

Tous les modules utilisent désormais le nouveau shell. La migration interne recommandée suit cet ordre :

1. Clients, rendez-vous et inventaire.
2. Facturation, comptabilité et rapports.
3. Administration, profil et écrans d’authentification.

Pour chaque écran : remplacer les contrôles historiques par `x-ui.*`, conserver les actions Livewire existantes, ajouter un état vide/chargement/erreur, puis valider le rendu mobile, tablette et bureau.

## Critères de validation

- `php artisan view:cache`
- `npm run build`
- tests métier POS (ouverture, stock, remise, paiement, points, clôture)
- vérification tactile 1024×768 et responsive 390×844
- impression thermique 80 mm
