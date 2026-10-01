<?php

/*
|--------------------------------------------------------------------------
| SOGEREF ERP — Registre des modules
|--------------------------------------------------------------------------
| Source unique de vérité pour :
|   • la navigation (groupes, libellés, icônes Heroicons, routes) ;
|   • les permissions générées ({clé}.view / {clé}.manage + actions) ;
|   • l'activation des modules depuis le Master système ;
|   • les libellés et tons de chaque statut métier.
*/

return [

    // ─────────────────────────────────────────────────────────────────────
    // 1. MODULES (ordre = ordre d'affichage dans la barre latérale)
    // ─────────────────────────────────────────────────────────────────────
    'groups' => [
        'personnel' => 'Espace personnel',
        'pilotage' => 'Pilotage',
        'accueil' => 'Réception',
        'relation' => 'Relations clients',
        'communication' => 'Communication & marketing',
        'ventes' => 'Ventes',
        'operations' => 'Opérations',
        'stock' => 'Stock & achats',
        'finance' => 'Finance',
        'rh' => 'Ressources humaines',
        'systeme' => 'Système',
    ],

    'modules' => [
        // Espace personnel : ouvert à tous les employés quel que soit leur rôle
        // (« everyone »). « menu » liste les sous-écrans pour le lanceur / la barre latérale.
        'personal' => [
            'group' => 'personnel', 'label' => 'Espace personnel', 'icon' => 'user-circle', 'route' => 'personal', 'core' => true, 'everyone' => true,
            'menu' => [
                ['label' => 'Ma journée', 'icon' => 'home', 'route' => 'personal'],
                ['label' => 'Mes tâches du jour', 'icon' => 'check-circle', 'route' => 'personal.tasks'],
                ['label' => 'Notes', 'icon' => 'pencil-square', 'route' => 'personal.notes'],
                ['label' => 'Tableurs', 'icon' => 'table-cells', 'route' => 'personal.sheets'],
                ['label' => 'Mes bons', 'icon' => 'ticket', 'route' => 'personal.vouchers'],
                ['label' => 'Mes congés', 'icon' => 'sun', 'route' => 'personal.leaves'],
            ],
        ],

        'dashboard' => ['group' => 'pilotage', 'label' => 'Tableau de bord', 'icon' => 'squares-2x2', 'route' => 'dashboard', 'core' => true],
        'orders' => ['group' => 'pilotage', 'label' => 'Suivi des commandes', 'icon' => 'arrow-path-rounded-square', 'route' => 'orders.index', 'actions' => ['validate']],

        // Réception : accueil, courrier, visites et rendez-vous.
        'reception' => [
            'group' => 'accueil', 'label' => 'Réception', 'icon' => 'building-office', 'route' => 'reception',
            'menu' => [
                ['label' => 'Accueil du jour', 'icon' => 'building-office', 'route' => 'reception'],
                ['label' => 'Courrier', 'icon' => 'envelope', 'route' => 'reception.mail'],
                ['label' => 'Visites', 'icon' => 'identification', 'route' => 'reception.visits'],
            ],
        ],
        'appointments' => ['group' => 'accueil', 'label' => 'Rendez-vous', 'icon' => 'calendar', 'route' => 'appointments'],

        // Relations clients : pipeline, contacts, annuaire, devis & pro forma, fidélité.
        'crm' => ['group' => 'relation', 'label' => 'Pipeline', 'icon' => 'funnel', 'route' => 'crm'],
        'contacts' => ['group' => 'relation', 'label' => 'Contacts', 'icon' => 'users', 'route' => 'contacts'],
        'directory' => ['group' => 'relation', 'label' => 'Annuaire', 'icon' => 'book-open', 'route' => 'directory'],
        'quotes' => ['group' => 'relation', 'label' => 'Devis & pro forma', 'icon' => 'document-text', 'route' => 'quotes'],
        'loyalty' => ['group' => 'relation', 'label' => 'Fidélité', 'icon' => 'gift', 'route' => 'loyalty'],

        // Communication & marketing : messagerie interne, WhatsApp, SMS, e-mails clients, campagnes.
        'messages' => ['group' => 'communication', 'label' => 'Messagerie interne', 'icon' => 'chat-bubble-left-right', 'route' => 'chat', 'core' => true],
        'communication' => [
            'group' => 'communication', 'label' => 'Communication', 'icon' => 'megaphone', 'route' => 'communication', 'actions' => ['send', 'campaign'],
            'menu' => [
                ['label' => 'Vue d’ensemble', 'icon' => 'presentation-chart-line', 'route' => 'communication'],
                ['label' => 'WhatsApp', 'icon' => 'chat-bubble-oval-left-ellipsis', 'route' => 'communication.whatsapp'],
                ['label' => 'SMS', 'icon' => 'device-phone-mobile', 'route' => 'communication.sms'],
                ['label' => 'E-mails clients', 'icon' => 'envelope-open', 'route' => 'communication.emails'],
                ['label' => 'Campagnes', 'icon' => 'megaphone', 'route' => 'communication.campaigns'],
                ['label' => 'Modèles', 'icon' => 'document-duplicate', 'route' => 'communication.templates'],
            ],
        ],

        'invoices' => ['group' => 'ventes', 'label' => 'Factures', 'icon' => 'document-currency-dollar', 'route' => 'invoices'],
        'pos' => ['group' => 'ventes', 'label' => 'Point de vente', 'icon' => 'shopping-cart', 'route' => 'pos'],
        'rentals' => ['group' => 'ventes', 'label' => 'Location', 'icon' => 'key', 'route' => 'rentals'],

        'manufacturing' => ['group' => 'operations', 'label' => 'Atelier & fabrication', 'icon' => 'wrench-screwdriver', 'route' => 'manufacturing'],
        'sites' => ['group' => 'operations', 'label' => 'Chantiers', 'icon' => 'home-modern', 'route' => 'sites'],
        'attendance' => ['group' => 'operations', 'label' => 'Listes de présence', 'icon' => 'clipboard-document-check', 'route' => 'attendance'],
        'projects' => ['group' => 'operations', 'label' => 'Projets', 'icon' => 'rectangle-stack', 'route' => 'projects'],
        'planning' => ['group' => 'operations', 'label' => 'Planification', 'icon' => 'calendar-days', 'route' => 'planning'],
        'timesheets' => ['group' => 'operations', 'label' => 'Feuilles de temps', 'icon' => 'clock', 'route' => 'timesheets'],

        'inventory' => ['group' => 'stock', 'label' => 'Stock & entrepôts', 'icon' => 'cube', 'route' => 'inventory'],
        'purchases' => ['group' => 'stock', 'label' => 'Achats & réappro', 'icon' => 'truck', 'route' => 'purchases'],
        'vouchers' => ['group' => 'stock', 'label' => 'Bons', 'icon' => 'ticket', 'route' => 'vouchers', 'actions' => ['approve', 'execute']],

        'cash' => ['group' => 'finance', 'label' => 'Caisse', 'icon' => 'banknotes', 'route' => 'cash'],
        'expense-claims' => ['group' => 'finance', 'label' => 'Notes de frais', 'icon' => 'receipt-percent', 'route' => 'expense-claims', 'actions' => ['approve']],
        'accounting' => ['group' => 'finance', 'label' => 'Comptabilité', 'icon' => 'calculator', 'route' => 'accounting', 'actions' => ['post']],
        'banking' => ['group' => 'finance', 'label' => 'Trésorerie', 'icon' => 'building-library', 'route' => 'banking'],
        'reports' => ['group' => 'finance', 'label' => 'Rapports', 'icon' => 'chart-bar-square', 'route' => 'reports'],

        'employees' => ['group' => 'rh', 'label' => 'Employés', 'icon' => 'identification', 'route' => 'employees'],
        'leaves' => ['group' => 'rh', 'label' => 'Congés', 'icon' => 'sun', 'route' => 'leaves', 'actions' => ['approve']],
        'payroll' => ['group' => 'rh', 'label' => 'Paie', 'icon' => 'wallet', 'route' => 'payroll', 'actions' => ['validate']],

        'system' => ['group' => 'systeme', 'label' => 'Master système', 'icon' => 'cog-8-tooth', 'route' => 'system', 'core' => true],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // 2. RÔLES PAR DÉFAUT (modules en lecture « view » ou gestion « manage »)
    // ─────────────────────────────────────────────────────────────────────
    'roles' => [
        'Super Admin' => '*',
        'Direction Générale' => ['view' => '*', 'manage' => ['orders', 'crm', 'quotes', 'invoices'], 'extra' => ['orders.validate', 'vouchers.approve', 'leaves.approve', 'payroll.validate', 'expense-claims.approve']],
        'Administrateur' => ['view' => '*', 'manage' => '*', 'extra' => ['system.view', 'system.manage', 'orders.validate', 'vouchers.approve', 'vouchers.execute', 'leaves.approve', 'payroll.validate', 'expense-claims.approve', 'accounting.post']],
        'Comptable' => ['view' => ['dashboard', 'orders', 'contacts', 'quotes', 'invoices', 'vouchers', 'cash', 'expense-claims', 'accounting', 'banking', 'reports', 'payroll', 'purchases', 'messages'], 'manage' => ['invoices', 'accounting', 'banking', 'expense-claims', 'reports'], 'extra' => ['accounting.post', 'expense-claims.approve']],
        'Caissier' => ['view' => ['dashboard', 'orders', 'contacts', 'invoices', 'pos', 'cash', 'vouchers', 'loyalty', 'messages'], 'manage' => ['cash', 'pos'], 'extra' => ['vouchers.execute']],
        'Commercial' => ['view' => ['dashboard', 'orders', 'crm', 'contacts', 'directory', 'loyalty', 'quotes', 'invoices', 'rentals', 'appointments', 'inventory', 'reports', 'messages', 'communication'], 'manage' => ['orders', 'crm', 'contacts', 'loyalty', 'quotes', 'rentals', 'appointments'], 'extra' => ['communication.send']],
        'Réceptionniste' => ['view' => ['dashboard', 'reception', 'appointments', 'contacts', 'directory', 'messages'], 'manage' => ['reception', 'appointments']],
        'Chargé de communication' => ['view' => ['dashboard', 'crm', 'contacts', 'directory', 'loyalty', 'quotes', 'communication', 'messages', 'reports'], 'manage' => ['communication', 'contacts'], 'extra' => ['communication.send', 'communication.campaign']],
        "Chef d'atelier" => ['view' => ['dashboard', 'orders', 'manufacturing', 'inventory', 'vouchers', 'planning', 'projects', 'timesheets', 'messages'], 'manage' => ['manufacturing', 'vouchers', 'planning', 'timesheets']],
        'Magasinier' => ['view' => ['dashboard', 'inventory', 'purchases', 'vouchers', 'manufacturing', 'sites', 'messages'], 'manage' => ['inventory', 'vouchers'], 'extra' => ['vouchers.execute']],
        'Acheteur' => ['view' => ['dashboard', 'inventory', 'purchases', 'vouchers', 'contacts', 'messages'], 'manage' => ['purchases', 'vouchers', 'contacts']],
        'Responsable RH' => ['view' => ['dashboard', 'employees', 'leaves', 'payroll', 'attendance', 'planning', 'sites', 'expense-claims', 'messages'], 'manage' => ['employees', 'leaves', 'payroll', 'attendance', 'planning'], 'extra' => ['leaves.approve']],
        'Chef de chantier' => ['view' => ['dashboard', 'sites', 'attendance', 'projects', 'planning', 'timesheets', 'vouchers', 'inventory', 'expense-claims', 'messages'], 'manage' => ['sites', 'attendance', 'projects', 'timesheets', 'vouchers', 'expense-claims']],
        'Auditeur' => ['view' => '*', 'manage' => []],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // 3. STATUTS MÉTIER — libellé + ton visuel (neutral, info, brand,
    //    warning, success, danger, accent)
    // ─────────────────────────────────────────────────────────────────────
    'statuses' => [
        'order_stage' => [
            'devis' => ['Devis', 'neutral'],
            'valide' => ['Validé client', 'info'],
            'facture' => ['Facturé', 'brand'],
            'paye' => ['Encaissé', 'accent'],
            'fabrication' => ['En fabrication', 'warning'],
            'execution' => ['En exécution', 'warning'],
            'livre' => ['Livré', 'success'],
            'cloture' => ['Clôturé', 'success'],
            'annule' => ['Annulé', 'danger'],
        ],
        'document' => [
            'draft' => ['Brouillon', 'neutral'], 'sent' => ['Envoyé', 'info'], 'viewed' => ['Consulté', 'info'],
            'accepted' => ['Accepté', 'success'], 'rejected' => ['Refusé', 'danger'], 'partially_paid' => ['Partiel', 'warning'],
            'paid' => ['Payé', 'success'], 'overdue' => ['En retard', 'danger'], 'cancelled' => ['Annulé', 'neutral'],
        ],
        'manufacturing' => [
            'draft' => ['Brouillon', 'neutral'], 'confirmed' => ['Planifié', 'info'], 'in_progress' => ['En production', 'warning'],
            'quality' => ['Contrôle qualité', 'accent'], 'done' => ['Terminé', 'success'], 'cancelled' => ['Annulé', 'danger'],
        ],
        'voucher' => [
            'draft' => ['Brouillon', 'neutral'], 'submitted' => ['À approuver', 'warning'], 'approved' => ['Approuvé', 'info'],
            'rejected' => ['Rejeté', 'danger'], 'executed' => ['Exécuté', 'success'], 'cancelled' => ['Annulé', 'neutral'],
        ],
        'site' => [
            'planned' => ['Planifié', 'neutral'], 'in_progress' => ['En cours', 'warning'], 'suspended' => ['Suspendu', 'danger'], 'completed' => ['Réceptionné', 'success'],
        ],
        'project' => [
            'draft' => ['Brouillon', 'neutral'], 'active' => ['Actif', 'info'], 'on_hold' => ['En pause', 'warning'], 'done' => ['Terminé', 'success'], 'cancelled' => ['Annulé', 'danger'],
        ],
        'task' => [
            'todo' => ['À faire', 'neutral'], 'in_progress' => ['En cours', 'warning'], 'review' => ['Revue', 'accent'], 'done' => ['Terminé', 'success'],
        ],
        'leave' => [
            'pending' => ['En attente', 'warning'], 'approved' => ['Approuvé', 'success'], 'rejected' => ['Refusé', 'danger'], 'cancelled' => ['Annulé', 'neutral'],
        ],
        'payroll' => [
            'draft' => ['Brouillon', 'neutral'], 'computed' => ['Calculée', 'info'], 'validated' => ['Validée', 'accent'], 'paid' => ['Payée', 'success'],
        ],
        'employee' => [
            'active' => ['Actif', 'success'], 'suspended' => ['Suspendu', 'warning'], 'left' => ['Sorti', 'neutral'],
        ],
        'attendance' => [
            'present' => ['Présent', 'success'], 'late' => ['Retard', 'warning'], 'half' => ['Demi-journée', 'accent'], 'absent' => ['Absent', 'danger'], 'leave' => ['Congé', 'info'],
        ],
        'appointment' => [
            'scheduled' => ['Planifié', 'neutral'], 'confirmed' => ['Confirmé', 'info'], 'done' => ['Honoré', 'success'], 'cancelled' => ['Annulé', 'danger'], 'no_show' => ['Absent', 'warning'],
        ],
        'rental' => [
            'reserved' => ['Réservé', 'info'], 'ongoing' => ['En cours', 'warning'], 'returned' => ['Retourné', 'accent'], 'invoiced' => ['Facturé', 'success'], 'cancelled' => ['Annulé', 'neutral'],
        ],
        'equipment' => [
            'available' => ['Disponible', 'success'], 'assigned' => ['Sur chantier', 'info'], 'rented' => ['En location', 'accent'], 'maintenance' => ['Maintenance', 'warning'], 'out_of_service' => ['Hors service', 'danger'],
        ],
        'expense_claim' => [
            'draft' => ['Brouillon', 'neutral'], 'submitted' => ['Soumise', 'warning'], 'approved' => ['Approuvée', 'info'], 'rejected' => ['Refusée', 'danger'], 'paid' => ['Remboursée', 'success'],
        ],
        'mail' => [
            'registered' => ['Enregistré', 'neutral'], 'forwarded' => ['Transmis', 'info'], 'processed' => ['Traité', 'success'], 'archived' => ['Archivé', 'neutral'],
        ],
        'timesheet' => [
            'draft' => ['Brouillon', 'neutral'], 'submitted' => ['Soumise', 'warning'], 'approved' => ['Approuvée', 'success'],
        ],
        'priority' => [
            'basse' => ['Basse', 'neutral'], 'normale' => ['Normale', 'info'], 'haute' => ['Haute', 'warning'], 'urgente' => ['Urgente', 'danger'],
        ],
    ],

    // ─────────────────────────────────────────────────────────────────────
    // 4. RÉFÉRENTIELS
    // ─────────────────────────────────────────────────────────────────────
    'voucher_types' => [
        'achat' => ['label' => "Bon d'achat", 'short' => 'Achat', 'prefix' => 'BA', 'icon' => 'shopping-bag'],
        'decaissement' => ['label' => 'Bon de décaissement', 'short' => 'Décaissement', 'prefix' => 'BD', 'icon' => 'banknotes'],
        'sortie' => ['label' => 'Bon de sortie matériel', 'short' => 'Sortie', 'prefix' => 'BS', 'icon' => 'arrow-up-tray'],
    ],

    'order_kinds' => [
        'vente' => 'Vente simple',
        'fabrication' => 'Fabrication atelier',
        'construction' => 'Construction / chantier',
        'service' => 'Prestation de service',
    ],

    'product_types' => [
        'finished' => 'Produit fini',
        'material' => 'Matière première',
        'consumable' => 'Consommable',
        'product' => 'Marchandise',
        'service' => 'Service',
    ],

    'units' => ['unité', 'pièce', 'm', 'm²', 'm³', 'kg', 'tonne', 'sac', 'barre', 'litre', 'rouleau', 'feuille', 'lot', 'forfait', 'jour', 'heure'],

    'expense_categories' => [
        'transport' => 'Transport', 'repas' => 'Repas', 'hebergement' => 'Hébergement', 'carburant' => 'Carburant',
        'communication' => 'Communication', 'fournitures' => 'Petites fournitures', 'chantier' => 'Frais de chantier', 'autre' => 'Autre',
    ],

    'document_templates' => [
        'moderne' => ['label' => 'Moderne', 'description' => 'Bandeau bleu SOGEREF, filet jaune, tableau aéré.', 'thumb' => 'band'],
        'classique' => ['label' => 'Classique', 'description' => 'Cadres émetteur / destinataire, format administratif.', 'thumb' => 'frame'],
        'epure' => ['label' => 'Épuré', 'description' => 'Noir & blanc minimal, grande typographie.', 'thumb' => 'minimal'],
        'chantier' => ['label' => 'BTP / Chantier', 'description' => 'Bloc projet, lots de travaux, visas et signatures.', 'thumb' => 'site'],
        'prestige' => ['label' => 'Prestige', 'description' => 'Bandeau jaune SOGEREF, cartouche client encadré, cachet et signature.', 'thumb' => 'gold'],
    ],

    // Modèles d'impression au choix du Master (Master système › Documents & impressions).
    // « thumb » pilote la miniature d'aperçu affichée dans le sélecteur.
    'print_templates' => [
        'receipt' => [
            'moderne' => ['label' => 'Moderne', 'description' => 'Bandeau bleu SOGEREF, montant mis en valeur.', 'thumb' => 'band'],
            'classique' => ['label' => 'Classique', 'description' => 'Cadres administratifs, montant en toutes lettres.', 'thumb' => 'frame'],
            'epure' => ['label' => 'Épuré', 'description' => 'Noir et blanc, économe en encre.', 'thumb' => 'minimal'],
            'carnet' => ['label' => 'Carnet à souche', 'description' => 'Reçu client + souche d’archive sur une même page.', 'thumb' => 'split'],
            'ticket' => ['label' => 'Ticket 80 mm', 'description' => 'Format imprimante thermique de caisse.', 'thumb' => 'ticket'],
        ],
        'exit_voucher' => [
            'standard' => ['label' => 'Standard', 'description' => 'Détail chiffré et circuit complet de visas.', 'thumb' => 'band'],
            'magasin' => ['label' => 'Magasin', 'description' => 'Liste de préparation : quantités demandées / sorties, cases à cocher.', 'thumb' => 'list'],
            'chantier' => ['label' => 'Livraison chantier', 'description' => 'Chantier, transport, réception sur site et réserves.', 'thumb' => 'site'],
            'epure' => ['label' => 'Épuré', 'description' => 'Noir et blanc, sans prix, signatures essentielles.', 'thumb' => 'minimal'],
            'duplicata' => ['label' => 'Duplicata', 'description' => 'Deux exemplaires (magasin / demandeur) sur une page.', 'thumb' => 'split'],
        ],
    ],

    // Comptes de charge proposés à la saisie des dépenses générales.
    'expense_accounts' => [
        '601' => 'Achats de marchandises', '602' => 'Matières premières', '605' => 'Autres achats (eau, électricité, fournitures)',
        '611' => 'Transport sur achats', '618' => 'Transport et déplacements', '622' => 'Loyers et charges locatives',
        '624' => 'Entretien et réparations', '625' => 'Assurances', '628' => 'Télécommunications et internet',
        '632' => 'Honoraires et conseils', '638' => 'Autres charges externes', '641' => 'Impôts et taxes',
    ],

    'appointment_types' => [
        'reunion' => 'Réunion', 'visite_chantier' => 'Visite de chantier', 'showroom' => 'Visite showroom',
        'mesure' => 'Prise de mesures', 'livraison' => 'Livraison / installation',
    ],

    // ─────────────────────────────────────────────────────────────────────
    // 5. PAIE RDC (valeurs par défaut, modifiables dans le Master système)
    // ─────────────────────────────────────────────────────────────────────
    'payroll' => [
        'cnss_employee_rate' => 5.0,       // part ouvrière
        'cnss_employer_rate' => 13.0,      // pension 5 % + risques pro 1,5 % + prestations familiales 6,5 %
        'inpp_rate' => 3.0,
        'onem_rate' => 0.2,
        'ipr_cap_rate' => 30.0,            // plafond de l'IPR en % du revenu imposable
        'ipr_dependent_reduction' => 2.0,  // réduction par personne à charge (max 9)
        'overtime_multiplier' => 1.3,
        'half_day_ratio' => 0.5,
        'working_days' => 26,
        // Barème mensuel progressif de l'IPR, en CDF.
        'ipr_brackets' => [
            ['up_to' => 162000, 'rate' => 0],
            ['up_to' => 1800000, 'rate' => 15],
            ['up_to' => 3600000, 'rate' => 30],
            ['up_to' => null, 'rate' => 40],
        ],
    ],
];
