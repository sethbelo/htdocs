# Yeboox POS

Application de gestion de point de vente pour restaurants, bars et commerces. Elle couvre le catalogue, les ventes, la caisse, la cuisine, les stocks, les réservations, les rapports et la comptabilité OHADA.

## Pile technique

- PHP 8.3+ et Laravel 13
- Livewire 3 et Flux UI 2
- Tailwind CSS 4, Vite 8 et Alpine.js
- SQLite pour le développement, avec prise en charge des bases configurées par Laravel
- Sanctum pour l’API et la synchronisation hors-ligne

## Installation

```bash
composer run setup
php artisan serve
```

Pour le développement complet :

```bash
composer run dev
```

## Configuration du kiosque

Définissez l’établissement public dans `.env` :

```dotenv
YEBOOX_KIOSK_ESTABLISHMENT_ID=uuid-de-l-etablissement
```

Sans cette valeur, le premier établissement est utilisé en environnement local.

## Qualité et validation

```bash
composer audit
npm audit
./vendor/bin/pint --test
php artisan test
npm run build
```

## Mise à jour

Les mises à jour mineures compatibles peuvent être appliquées ainsi :

```bash
composer update --with-all-dependencies
npm update
php artisan test
npm run build
```

Les changements majeurs de Livewire, PHPUnit, Intervention Image ou Spatie Permission doivent être réalisés séparément avec leurs guides de migration et une validation fonctionnelle complète.
