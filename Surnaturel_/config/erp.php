<?php

/*
|--------------------------------------------------------------------------
| Configuration métier de l'ERP
|--------------------------------------------------------------------------
| Modules, actions et rôles : les permissions sont générées sous la forme
| « module.action ». Le cloisonnement par siège est assuré par le
| rattachement utilisateur ↔ sièges (table site_user) et la permission
| « sites.view_all » pour la vue consolidée.
*/

return [

    'modules' => [
        'dashboard' => 'Tableau de bord',
        'sites' => 'Sièges & structure',
        'users' => 'Utilisateurs',
        'roles' => 'Rôles & permissions',
        'clients' => 'Clients',
        'pipeline' => 'Pipeline commercial',
        'loyalty' => 'Fidélisation',
        'followups' => 'Suivi clients & relances (marketing)',
        'quotes' => 'Devis & pro forma',
        'orders' => 'Commandes',
        'cash' => 'Caisse',
        'pos' => 'Point de vente',
        'invoices' => 'Facturation',
        'payments' => 'Paiements',
        'design' => 'Graphisme',
        'proofs' => 'BAT',
        'production' => 'Salle d’impression',
        'machines' => 'Parc machines',
        'deliveries' => 'Livraisons',
        'products' => 'Articles',
        'inventory' => 'Stocks & inventaire',
        'warehouses' => 'Entrepôts',
        'suppliers' => 'Fournisseurs',
        'purchases' => 'Achats',
        'approvals' => 'Circuits de validation',
        'vouchers' => 'Bons',
        'accounting' => 'Comptabilité',
        'treasury' => 'Trésorerie',
        'expenses' => 'Notes de frais',
        'departments' => 'Départements',
        'employees' => 'Employés',
        'attendance' => 'Présence',
        'leaves' => 'Congés',
        'payroll' => 'Paie',
        'cards' => 'Badges, cartes de service & cartes de visite',
        'affiliations' => 'Affiliations santé & assurance vie',
        'documents' => 'GED',
        'tasks' => 'Tâches & collaboration',
        'reports' => 'Rapports',
        'settings' => 'Paramètres',
        'audit' => 'Audit & journaux',
    ],

    'actions' => [
        'view' => 'Voir',
        'create' => 'Créer',
        'update' => 'Modifier',
        'delete' => 'Supprimer',
        'validate' => 'Valider',
        'cancel' => 'Annuler',
        'print' => 'Imprimer',
        'export' => 'Exporter',
        'collect' => 'Encaisser',
        'disburse' => 'Décaisser',
        'approve' => 'Approuver',
    ],

    /*
    | Permissions spéciales, hors matrice module × action.
    */
    'special_permissions' => [
        'sites.view_all' => 'Vue consolidée tous sièges',
        'employees.view_sensitive' => 'Voir les données RH sensibles (salaire, banque, pièce d’identité)',
        'pos.discount' => 'Accorder une remise en caisse',
        'pos.change_price' => 'Modifier un prix en caisse',
        'design.work' => 'Réaliser des travaux graphiques',
        'deliveries.release_unpaid' => 'Remettre une commande non soldée',
        'audit.view_logins' => 'Consulter le journal des connexions',
        'direction.payroll_sign' => 'Visa final des fiches de paie (Direction générale)',
    ],

    /*
    | Rôles : motifs de permissions (« * » = toutes les actions ou tous les modules).
    */
    'roles' => [
        // Le Directeur Général est le seul à disposer de toutes les fonctionnalités (voir aussi Gate::before).
        'directeur-general' => ['label' => 'Directeur Général', 'home' => 'dashboard', 'permissions' => ['*']],
        'super-admin' => ['label' => 'Master / Super Administrateur (technique)', 'home' => 'settings.users', 'permissions' => ['settings.*', 'users.*', 'roles.*', 'sites.*', 'audit.*', 'audit.view_logins', 'approvals.view', 'approvals.update', 'documents.view', 'tasks.*']],
        'directeur' => ['label' => 'Directeur', 'home' => 'dashboard', 'permissions' => ['dashboard.view', 'orders.view', 'orders.export', 'orders.approve', 'quotes.view', 'clients.view', 'pipeline.view', 'design.view', 'proofs.view', 'proofs.approve', 'production.view', 'machines.view', 'deliveries.view', 'inventory.view', 'products.view', 'warehouses.view', 'purchases.view', 'purchases.approve', 'invoices.view', 'approvals.*', 'reports.*', 'tasks.*', 'documents.view']],
        'responsable-administratif' => ['label' => 'Responsable administratif', 'home' => 'approvals.index', 'permissions' => ['cards.view', 'cards.print', 'affiliations.view', 'documents.*', 'vouchers.*', 'approvals.*', 'employees.view', 'departments.view', 'purchases.view', 'purchases.approve', 'suppliers.view', 'tasks.*', 'reports.view']],
        'responsable-financier' => ['label' => 'Responsable financier', 'home' => 'treasury.index', 'permissions' => ['accounting.*', 'treasury.*', 'invoices.*', 'payments.*', 'expenses.*', 'cash.view', 'cash.approve', 'cash.disburse', 'reports.*', 'payroll.view', 'payroll.approve', 'payroll.disburse', 'purchases.view', 'purchases.approve', 'suppliers.view', 'approvals.*', 'vouchers.*', 'clients.view', 'tasks.*', 'documents.view']],
        'comptable' => ['label' => 'Comptable', 'home' => 'accounting.index', 'permissions' => ['accounting.*', 'invoices.view', 'invoices.create', 'invoices.print', 'invoices.export', 'payments.view', 'payments.export', 'treasury.view', 'cash.view', 'expenses.view', 'reports.view', 'reports.export', 'clients.view', 'suppliers.view', 'tasks.*', 'documents.view']],
        'responsable-rh' => ['label' => 'Responsable RH', 'home' => 'hr.employees', 'permissions' => ['cards.*', 'affiliations.*', 'employees.*', 'employees.view_sensitive', 'attendance.*', 'leaves.*', 'payroll.*', 'departments.*', 'expenses.view', 'expenses.approve', 'reports.view', 'reports.export', 'documents.*', 'tasks.*', 'approvals.view', 'approvals.approve']],
        'responsable-marketing' => ['label' => 'Responsable marketing', 'home' => 'followups.index', 'permissions' => ['followups.*', 'clients.*', 'pipeline.*', 'loyalty.*', 'quotes.view', 'orders.view', 'invoices.view', 'reports.view', 'reports.export', 'tasks.*', 'documents.view', 'documents.create']],
        'agent-marketing' => ['label' => 'Agent marketing / relation client', 'home' => 'followups.index', 'permissions' => ['followups.view', 'followups.create', 'followups.update', 'followups.print', 'clients.view', 'clients.create', 'clients.update', 'pipeline.view', 'pipeline.create', 'pipeline.update', 'loyalty.view', 'quotes.view', 'orders.view', 'tasks.view', 'tasks.update']],
        'responsable-commercial' => ['label' => 'Responsable commercial', 'home' => 'orders.index', 'permissions' => ['followups.*', 'clients.*', 'pipeline.*', 'loyalty.*', 'quotes.*', 'orders.*', 'invoices.view', 'invoices.print', 'proofs.view', 'proofs.validate', 'deliveries.view', 'deliveries.create', 'reports.view', 'reports.export', 'tasks.*', 'documents.view', 'documents.create']],
        'commercial' => ['label' => 'Commercial', 'home' => 'orders.index', 'permissions' => ['followups.view', 'followups.create', 'followups.update', 'clients.view', 'clients.create', 'clients.update', 'pipeline.view', 'pipeline.create', 'pipeline.update', 'quotes.view', 'quotes.create', 'quotes.update', 'quotes.print', 'orders.view', 'orders.create', 'orders.update', 'orders.print', 'proofs.view', 'proofs.validate', 'loyalty.view', 'deliveries.view', 'tasks.*', 'documents.view', 'documents.create']],
        'responsable-entrepot' => ['label' => 'Responsable entrepôt', 'home' => 'inventory.report', 'permissions' => ['inventory.*', 'products.*', 'warehouses.view', 'warehouses.update', 'purchases.view', 'purchases.create', 'purchases.update', 'purchases.validate', 'suppliers.*', 'vouchers.view', 'vouchers.create', 'reports.view', 'reports.export', 'tasks.*']],
        'magasinier' => ['label' => 'Magasinier', 'home' => 'inventory.operations', 'permissions' => ['inventory.view', 'inventory.create', 'inventory.update', 'inventory.print', 'inventory.export', 'products.view', 'warehouses.view', 'purchases.view', 'purchases.update', 'tasks.view', 'tasks.update']],
        'responsable-impression' => ['label' => 'Responsable impression', 'home' => 'production.board', 'permissions' => ['production.*', 'machines.*', 'orders.view', 'proofs.view', 'proofs.approve', 'design.view', 'inventory.view', 'inventory.create', 'deliveries.view', 'deliveries.create', 'reports.view', 'tasks.*']],
        'imprimeur' => ['label' => 'Imprimeur / Opérateur machine', 'home' => 'production.board', 'permissions' => ['production.view', 'production.update', 'machines.view', 'orders.view', 'inventory.view', 'tasks.view', 'tasks.update']],
        'responsable-graphisme' => ['label' => 'Responsable Graphisme', 'home' => 'design.board', 'permissions' => ['design.*', 'design.work', 'proofs.*', 'orders.view', 'reports.view', 'tasks.*', 'documents.view']],
        'graphiste' => ['label' => 'Graphiste', 'home' => 'design.board', 'permissions' => ['design.view', 'design.update', 'design.work', 'proofs.view', 'proofs.create', 'orders.view', 'tasks.view', 'tasks.update']],
        'responsable-boutique' => ['label' => 'Responsable boutique', 'home' => 'pos', 'permissions' => ['pos.*', 'pos.discount', 'pos.change_price', 'cash.*', 'inventory.view', 'inventory.create', 'inventory.update', 'products.view', 'clients.view', 'clients.create', 'loyalty.*', 'orders.view', 'invoices.view', 'invoices.print', 'reports.view', 'tasks.*']],
        'gerant' => ['label' => 'Gérant', 'home' => 'pos', 'permissions' => ['pos.*', 'pos.discount', 'cash.*', 'inventory.view', 'inventory.create', 'inventory.update', 'products.view', 'clients.*', 'loyalty.*', 'orders.view', 'orders.create', 'invoices.view', 'invoices.print', 'reports.view', 'tasks.*']],
        'caissier' => ['label' => 'Caissier', 'home' => 'pos', 'permissions' => ['cash.view', 'cash.collect', 'pos.view', 'pos.create', 'pos.print', 'orders.view', 'orders.print', 'clients.view', 'clients.create', 'loyalty.view', 'invoices.view', 'invoices.print', 'inventory.view', 'tasks.view']],
        'livreur' => ['label' => 'Livreur', 'home' => 'deliveries.index', 'permissions' => ['deliveries.view', 'deliveries.update', 'orders.view', 'tasks.view', 'tasks.update']],
        'auditeur' => ['label' => 'Auditeur', 'home' => 'accounting.index', 'permissions' => ['accounting.view', 'accounting.export', 'invoices.view', 'invoices.export', 'payments.view', 'payments.export', 'treasury.view', 'treasury.export', 'cash.view', 'inventory.view', 'inventory.export', 'purchases.view', 'expenses.view', 'reports.view', 'reports.export', 'audit.view', 'audit.export', 'audit.view_logins']],
    ],

    /*
    | Coupures pour le comptage de caisse à la clôture (par devise).
    */
    'cash' => [
        'denominations' => [
            'USD' => [100, 50, 20, 10, 5, 2, 1],
            'CDF' => [20000, 10000, 5000, 1000, 500, 200, 100, 50],
            'EUR' => [200, 100, 50, 20, 10, 5, 2, 1, 0.5],
        ],
    ],

    /*
    | Affiche les comptes de démonstration sur l’écran de connexion (environnement local uniquement).
    */
    'demo_accounts' => env('ERP_DEMO_ACCOUNTS', true),

    'pos' => [
        'ticket_width_mm' => 80,
    ],

    'mapbox' => [
        'token' => env('MAPBOX_TOKEN'),
        'style' => env('MAPBOX_STYLE', 'mapbox://styles/mapbox/streets-v12'),
        'center' => [15.3136, -4.3317],
    ],

    'whatsapp' => [
        'enabled' => env('WHATSAPP_ENABLED', false),
        'endpoint' => env('WHATSAPP_ENDPOINT'),
        'token' => env('WHATSAPP_TOKEN'),
    ],
];
