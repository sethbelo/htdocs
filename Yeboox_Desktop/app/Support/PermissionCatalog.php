<?php

namespace App\Support;

/**
 * Source unique des autorisations fonctionnelles de l'application.
 *
 * Les rôles regroupent ces permissions, mais aucun contrôleur ni composant ne
 * doit se fier au nom d'un rôle pour autoriser une opération métier.
 */
final class PermissionCatalog
{
    public const ALL = [
        'view dashboard',
        'manage POS',
        'manage register',
        'manage inventory',
        'export inventory',
        'manage invoices',
        'manage accounting',
        'manage reports',
        'export reports',
        'manage sales',
        'export sales',
        'manage appointments',
        'manage customers',
        'manage operations',
        'approve operations',
        'manage administration',
        'manage users',
        'manage roles',
        'manage settings',
    ];

    public const MANAGER = [
        'view dashboard',
        'manage POS',
        'manage register',
        'manage inventory',
        'export inventory',
        'manage invoices',
        'manage accounting',
        'manage reports',
        'export reports',
        'manage sales',
        'export sales',
        'manage appointments',
        'manage customers',
        'manage operations',
        'approve operations',
    ];

    public const CASHIER = [
        'view dashboard',
        'manage POS',
        'manage register',
        'manage appointments',
        'manage customers',
    ];
}
