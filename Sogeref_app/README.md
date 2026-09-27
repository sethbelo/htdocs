# SOGEREF ERP

Progiciel de gestion intégré de **SOGEREF Sarl** — *Sculptons vos rêves, bâtissons la réalité.*
Laravel 13 · Livewire 4 · Tailwind CSS 4 · SQLite (ou MySQL) · version Desktop Electron.

## Démarrage

```bash
composer install
npm install
php artisan migrate:fresh --seed
npm run build
php artisan serve --port=8010
```

Ouvrez http://localhost:8010. Mot de passe de tous les comptes de démonstration : **Sogeref@2026**.

| Compte | Rôle |
|---|---|
| master@sogeref.cd | Super Admin (Master système) |
| direction@sogeref.cd | Direction Générale (approbations) |
| admin@sogeref.cd | Administrateur |
| compta@sogeref.cd | Comptable |
| caisse@sogeref.cd | Caissier |
| commercial@sogeref.cd | Commercial |
| accueil@sogeref.cd | Réceptionniste |
| atelier@sogeref.cd | Chef d’atelier |
| magasin@sogeref.cd | Magasinier |
| achats@sogeref.cd | Acheteur |
| rh@sogeref.cd | Responsable RH |
| chantier@sogeref.cd | Chef de chantier |

## Modules

| Domaine | Modules |
|---|---|
| Pilotage | Tableau de bord, **suivi des commandes** (devis → validation → facture → caisse → atelier / chantier → livraison → clôture) |
| Réception & CRM | Réception (visiteurs, badges, courrier, demandes), pipeline CRM, clients B2B/B2C & fournisseurs, fidélité (points, paliers, remises) |
| Ventes | Devis, factures (4 modèles d’impression), point de vente tactile, location de matériel |
| Opérations | Atelier & fabrication (ordres, nomenclatures, fiche de fabrication, liste de matériel), chantiers (équipe, présences, matériel, rapports, bons, paie, évaluation), listes de présence, projets, planification, rendez-vous, feuilles de temps |
| Stock & achats | Articles, entrepôts, mouvements, inventaires, parc matériel, réapprovisionnement automatique, bons d’achat / décaissement / sortie avec circuit d’approbation |
| Finance | Caisse (sessions, écarts), notes de frais, comptabilité SYSCOHADA (journaux, grand livre, balance), trésorerie & dépenses, rapports |
| RH & paie | Employés (permanents, journaliers), congés, paie RDC (CNSS 5 %, IPR progressif, avances, charges patronales), fiches de paie |
| Système | Master système (société, modèles, modules, numérotation, règles de paie, utilisateurs, rôles & droits, audit, sauvegarde), messagerie |

## Architecture

- `config/erp.php` : registre des modules (menu, droits, activation), statuts, référentiels, paramètres de paie.
- `app/Services` : règles métier (OrderWorkflow, ManufacturingService, VoucherService, StockService, PayrollService, CashService, PosService, ReplenishmentService, LoyaltyService, RentalService, HrService, ReportService…).
- `app/Actions/Accounting/SynchronizeAccounting.php` : écritures comptables automatiques (ventes, caisse, bons, paie, notes de frais).
- `app/Livewire/*` : un écran par module. `resources/views/components/ui` : composants d’interface.
- `resources/css/app.css` : charte graphique commentée (bleu #104A63, jaune #FFCE31, turquoise #3ACECE, rouge #F7313A).
- `resources/views/pdf` : impressions sur papier à en-tête (documents, bons, fiche de fabrication, bulletins, présences, rapports, ticket 80 mm).

## Tests

```bash
php artisan test
```

## Desktop

```bash
npm run desktop          # développement
npm run desktop:build    # installateur
```
