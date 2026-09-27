<?php

namespace Database\Seeders;

use App\Models\Partner;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach ([
            [
                'slug' => 'starter', 'name' => 'Essentiel', 'eyebrow' => 'Pour démarrer',
                'tagline' => 'Le nécessaire pour vendre proprement dès le premier jour.',
                'monthly_price' => 19, 'annual_price' => 190, 'setup_fee' => 25,
                'max_locations' => 1, 'is_highlighted' => false, 'sort_order' => 10,
                'features' => ['Point de vente complet', 'Produits & inventaire', 'Tickets thermiques', '1 point de vente', 'Support standard'],
            ],
            [
                'slug' => 'professional', 'name' => 'Pro', 'eyebrow' => 'Le plus choisi',
                'tagline' => 'Pilotez les ventes, les équipes et la comptabilité au même endroit.',
                'monthly_price' => 39, 'annual_price' => 390, 'setup_fee' => 49,
                'max_locations' => 3, 'is_highlighted' => true, 'sort_order' => 20,
                'features' => ['Tout Essentiel', 'Comptabilité SYSCOHADA', 'Fidélité & clients', 'Rapports avancés', 'Jusqu’à 3 points de vente', 'Support prioritaire'],
            ],
            [
                'slug' => 'enterprise', 'name' => 'Réseau', 'eyebrow' => 'Multi-sites',
                'tagline' => 'Une vision consolidée pour les enseignes qui grandissent.',
                'monthly_price' => 79, 'annual_price' => 790, 'setup_fee' => 99,
                'max_locations' => 99, 'is_highlighted' => false, 'sort_order' => 30,
                'features' => ['Tout Pro', 'Points de vente illimités', 'API & application mobile', 'Rôles avancés', 'Accompagnement au déploiement', 'Support dédié'],
            ],
        ] as $plan) {
            SubscriptionPlan::query()->updateOrCreate(['name' => $plan['name']], $plan + ['currency' => 'USD', 'is_active' => true]);
        }

        foreach ([
            [
                'name' => 'Yetoo Technologie', 'slug' => 'yetoo-technologie', 'category' => 'Technologie',
                'tagline' => 'Partenaire technologique de Yeboox', 'initials' => 'YT',
                'website_url' => 'https://cd.linkedin.com/in/yetoo-tech-54a6352a7',
                'description' => 'L’équipe technologique qui conçoit et accompagne le déploiement de Yeboox auprès des entreprises africaines.',
                'logo_path' => 'images/brand/yeboox-icon.png',
                'gallery_images' => ['images/marketing/yeboox-african-retail-pos.png', 'images/marketing/yeboox-restaurant-pos.png', 'images/marketing/yeboox-business-analytics.png'],
                'brand_color' => '#3154F4', 'is_verified' => true, 'is_featured' => true, 'sort_order' => 10,
            ],
            [
                'name' => 'Commerce Kinshasa', 'slug' => 'commerce-kinshasa-demo', 'category' => 'Commerce',
                'tagline' => 'Environnement partenaire de démonstration', 'initials' => 'CK',
                'description' => 'Une boutique pilote qui centralise ses ventes, ses stocks et son suivi client avec Yeboox.',
                'logo_path' => 'images/partners/commerce-kinshasa.svg',
                'gallery_images' => ['images/marketing/yeboox-african-retail-pos.png', 'images/marketing/yeboox-business-analytics.png'],
                'brand_color' => '#D97706', 'is_verified' => false, 'is_featured' => false, 'sort_order' => 20,
            ],
            [
                'name' => 'Services Kinshasa', 'slug' => 'services-kinshasa-demo', 'category' => 'Services',
                'tagline' => 'Environnement partenaire de démonstration', 'initials' => 'SK',
                'description' => 'Un établissement de services pilote qui utilise Yeboox pour organiser ses opérations et ses rendez-vous.',
                'logo_path' => 'images/partners/services-kinshasa.svg',
                'gallery_images' => ['images/marketing/yeboox-business-analytics.png', 'images/marketing/yeboox-restaurant-pos.png'],
                'brand_color' => '#7C3AED', 'is_verified' => false, 'is_featured' => false, 'sort_order' => 30,
            ],
        ] as $partner) {
            Partner::query()->updateOrCreate(['slug' => $partner['slug']], $partner + ['is_active' => true]);
        }
    }
}
