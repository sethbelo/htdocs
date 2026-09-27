<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;

final class PriceGridCatalog
{
    public const DEFAULT_TEMPLATE = 'signature';

    /**
     * Les mêmes jetons alimentent les cartes de sélection, l'aperçu et le PDF.
     */
    public static function templates(): array
    {
        return [
            'signature' => ['label' => 'Signature', 'description' => 'Éditorial violet premium', 'icon' => 'sparkles', 'accent' => '#6d28d9', 'dark' => '#2e1065', 'soft' => '#f5f3ff', 'border' => '#ddd6fe', 'layout' => 'cards', 'shape' => 'rounded'],
            'minimal' => ['label' => 'Minimal', 'description' => 'Lignes fines et espace blanc', 'icon' => 'bars-3-bottom-left', 'accent' => '#27272a', 'dark' => '#09090b', 'soft' => '#fafafa', 'border' => '#d4d4d8', 'layout' => 'list', 'shape' => 'square'],
            'corporate' => ['label' => 'Corporate', 'description' => 'Catalogue bleu institutionnel', 'icon' => 'building-office-2', 'accent' => '#0369a1', 'dark' => '#0c4a6e', 'soft' => '#f0f9ff', 'border' => '#bae6fd', 'layout' => 'table', 'shape' => 'soft'],
            'bistro' => ['label' => 'Bistro', 'description' => 'Menu chaleureux et direct', 'icon' => 'cake', 'accent' => '#ea580c', 'dark' => '#7c2d12', 'soft' => '#fff7ed', 'border' => '#fed7aa', 'layout' => 'menu', 'shape' => 'rounded'],
            'prestige' => ['label' => 'Prestige', 'description' => 'Noir, ivoire et doré', 'icon' => 'trophy', 'accent' => '#a16207', 'dark' => '#1c1917', 'soft' => '#fffbeb', 'border' => '#fde68a', 'layout' => 'list', 'shape' => 'square'],
            'botanique' => ['label' => 'Botanique', 'description' => 'Vert naturel et organique', 'icon' => 'sparkles', 'accent' => '#047857', 'dark' => '#064e3b', 'soft' => '#ecfdf5', 'border' => '#a7f3d0', 'layout' => 'cards', 'shape' => 'organic'],
            'retro' => ['label' => 'Rétro', 'description' => 'Affiche commerciale expressive', 'icon' => 'radio', 'accent' => '#be123c', 'dark' => '#4c0519', 'soft' => '#fff1f2', 'border' => '#fecdd3', 'layout' => 'menu', 'shape' => 'ticket'],
            'digital' => ['label' => 'Digital', 'description' => 'Grille technique cyan', 'icon' => 'cpu-chip', 'accent' => '#0891b2', 'dark' => '#083344', 'soft' => '#ecfeff', 'border' => '#a5f3fc', 'layout' => 'table', 'shape' => 'cut'],
            'catalogue' => ['label' => 'Catalogue', 'description' => 'Colonnes denses et lisibles', 'icon' => 'book-open', 'accent' => '#4f46e5', 'dark' => '#1e1b4b', 'soft' => '#eef2ff', 'border' => '#c7d2fe', 'layout' => 'columns', 'shape' => 'soft'],
            'mosaique' => ['label' => 'Mosaïque', 'description' => 'Blocs colorés contemporains', 'icon' => 'squares-2x2', 'accent' => '#0f766e', 'dark' => '#134e4a', 'soft' => '#f0fdfa', 'border' => '#99f6e4', 'layout' => 'mosaic', 'shape' => 'rounded'],
            'atelier' => ['label' => 'Atelier', 'description' => 'Tarifs sobres façon fiche métier', 'icon' => 'wrench-screwdriver', 'accent' => '#52525b', 'dark' => '#18181b', 'soft' => '#f4f4f5', 'border' => '#d4d4d8', 'layout' => 'columns', 'shape' => 'square'],
            'celebration' => ['label' => 'Célébration', 'description' => 'Accents lumineux et dynamiques', 'icon' => 'star', 'accent' => '#c026d3', 'dark' => '#701a75', 'soft' => '#fdf4ff', 'border' => '#f5d0fe', 'layout' => 'mosaic', 'shape' => 'pill'],
        ];
    }

    public static function template(string $key): array
    {
        return self::templates()[$key] ?? self::templates()[self::DEFAULT_TEMPLATE];
    }

    public static function hasTemplate(string $key): bool
    {
        return array_key_exists($key, self::templates());
    }

    public static function products(?string $category = null): Collection
    {
        return Product::query()
            ->with('unitOfMeasure')
            ->where('is_active', true)
            ->when($category, fn ($query, string $value) => $query->where('category', $value))
            ->orderBy('category')
            ->orderBy('name')
            ->get();
    }

    public static function grouped(?string $category = null): Collection
    {
        return self::products($category)
            ->groupBy(fn (Product $product) => trim((string) $product->category) ?: 'Sans catégorie');
    }
}
