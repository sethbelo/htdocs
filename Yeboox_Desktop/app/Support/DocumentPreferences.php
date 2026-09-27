<?php

namespace App\Support;

use App\Models\Setting;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\Storage;

final class DocumentPreferences
{
    public static function receiptTemplates(): array
    {
        return [
            'classic' => ['label' => 'Classique', 'description' => 'Centré et universel'],
            'compact' => ['label' => 'Compact', 'description' => 'Dense pour économiser le papier'],
            'modern' => ['label' => 'Moderne', 'description' => 'Titres forts et lignes nettes'],
            'elegant' => ['label' => 'Élégant', 'description' => 'Typographie raffinée'],
            'boxed' => ['label' => 'Encadré', 'description' => 'Informations structurées'],
            'minimal' => ['label' => 'Minimal', 'description' => 'Essentiel et très lisible'],
            'bold' => ['label' => 'Impact', 'description' => 'Total et marque renforcés'],
            'friendly' => ['label' => 'Accueil', 'description' => 'Souple et chaleureux'],
            'fiscal' => ['label' => 'Fiscal', 'description' => 'Mentions légales prioritaires'],
            'premium' => ['label' => 'Premium', 'description' => 'Contraste élégant et signature'],
            'ribbon' => ['label' => 'Ruban', 'description' => 'En-tête graphique et dynamique'],
            'eco' => ['label' => 'Éco', 'description' => 'Ultra-compact et économe'],
            'transaction' => ['label' => 'Transaction', 'description' => 'Référence, date et agent en tête'],
            'playlist' => ['label' => 'Liste', 'description' => 'Articles en lignes façon playlist'],
            'ledger_receipt' => ['label' => 'Registre', 'description' => 'Colonnes comptables structurées'],
            'vat_summary' => ['label' => 'Récap TVA', 'description' => 'Synthèse fiscale mise en avant'],
            'merchant' => ['label' => 'Commerçant', 'description' => 'Identité et contact prioritaires'],
            'columnar' => ['label' => 'Colonnes', 'description' => 'Quantités et montants alignés'],
            'timestamp' => ['label' => 'Horodaté', 'description' => 'Date, heure et utilisateur dominants'],
            'loyalty' => ['label' => 'Fidélité', 'description' => 'Message client et retour encouragé'],
        ];
    }

    public static function reportTemplates(): array
    {
        return [
            'executive' => ['label' => 'Exécutif', 'description' => 'Direction et synthèse'],
            'minimal' => ['label' => 'Minimal', 'description' => 'Blanc et précis'],
            'corporate' => ['label' => 'Corporate', 'description' => 'Institutionnel'],
            'editorial' => ['label' => 'Éditorial', 'description' => 'Hiérarchie magazine'],
            'geometric' => ['label' => 'Géométrique', 'description' => 'Accents visuels'],
            'ledger' => ['label' => 'Registre', 'description' => 'Comptabilité classique'],
            'elegant' => ['label' => 'Élégant', 'description' => 'Sobre et premium'],
            'modern' => ['label' => 'Moderne', 'description' => 'Données mises en avant'],
            'framed' => ['label' => 'Encadré', 'description' => 'Cadre administratif'],
            'compact' => ['label' => 'Compact', 'description' => 'Maximum de données'],
            'official' => ['label' => 'Officiel', 'description' => 'Administration et conformité'],
            'heritage' => ['label' => 'Héritage', 'description' => 'Institutionnel et traditionnel'],
            'sahara' => ['label' => 'Sahara', 'description' => 'Chaleureux et éditorial'],
            'digital' => ['label' => 'Digital', 'description' => 'Technique et analytique'],
        ];
    }

    /**
     * Visual tokens used by both the browser preview and the generated PDF.
     * Keeping them here prevents a report from silently falling back to a
     * hard-coded design that differs from the model selected in Configuration.
     */
    public static function reportThemes(): array
    {
        return [
            'executive' => ['accent' => '#4f46e5', 'dark' => '#1e1b4b', 'soft' => '#eef2ff', 'border' => '#c7d2fe', 'muted' => '#6366f1', 'radius' => '6px', 'header' => 'band', 'density' => 'normal'],
            'minimal' => ['accent' => '#27272a', 'dark' => '#18181b', 'soft' => '#fafafa', 'border' => '#d4d4d8', 'muted' => '#71717a', 'radius' => '0', 'header' => 'line', 'density' => 'normal'],
            'corporate' => ['accent' => '#0369a1', 'dark' => '#0c4a6e', 'soft' => '#f0f9ff', 'border' => '#bae6fd', 'muted' => '#0284c7', 'radius' => '4px', 'header' => 'sidebar', 'density' => 'normal'],
            'editorial' => ['accent' => '#9f1239', 'dark' => '#4c0519', 'soft' => '#fff1f2', 'border' => '#fecdd3', 'muted' => '#be123c', 'radius' => '2px', 'header' => 'editorial', 'density' => 'spacious'],
            'geometric' => ['accent' => '#0f766e', 'dark' => '#134e4a', 'soft' => '#f0fdfa', 'border' => '#99f6e4', 'muted' => '#0d9488', 'radius' => '12px 0', 'header' => 'geometric', 'density' => 'normal'],
            'ledger' => ['accent' => '#3f3f46', 'dark' => '#18181b', 'soft' => '#f4f4f5', 'border' => '#a1a1aa', 'muted' => '#52525b', 'radius' => '0', 'header' => 'double', 'density' => 'compact'],
            'elegant' => ['accent' => '#a16207', 'dark' => '#422006', 'soft' => '#fffbeb', 'border' => '#fde68a', 'muted' => '#b45309', 'radius' => '0', 'header' => 'elegant', 'density' => 'spacious'],
            'modern' => ['accent' => '#7c3aed', 'dark' => '#4c1d95', 'soft' => '#f5f3ff', 'border' => '#ddd6fe', 'muted' => '#8b5cf6', 'radius' => '10px', 'header' => 'modern', 'density' => 'normal'],
            'framed' => ['accent' => '#047857', 'dark' => '#064e3b', 'soft' => '#ecfdf5', 'border' => '#6ee7b7', 'muted' => '#059669', 'radius' => '0', 'header' => 'framed', 'density' => 'normal'],
            'compact' => ['accent' => '#0e7490', 'dark' => '#164e63', 'soft' => '#ecfeff', 'border' => '#a5f3fc', 'muted' => '#0891b2', 'radius' => '3px', 'header' => 'compact', 'density' => 'compact'],
            'official' => ['accent' => '#1d4ed8', 'dark' => '#172554', 'soft' => '#eff6ff', 'border' => '#93c5fd', 'muted' => '#2563eb', 'radius' => '2px', 'header' => 'official', 'density' => 'normal'],
            'heritage' => ['accent' => '#9f1239', 'dark' => '#500724', 'soft' => '#fff1f2', 'border' => '#fda4af', 'muted' => '#be123c', 'radius' => '0', 'header' => 'heritage', 'density' => 'spacious'],
            'sahara' => ['accent' => '#c2410c', 'dark' => '#7c2d12', 'soft' => '#fff7ed', 'border' => '#fdba74', 'muted' => '#ea580c', 'radius' => '8px 2px', 'header' => 'sahara', 'density' => 'spacious'],
            'digital' => ['accent' => '#0891b2', 'dark' => '#083344', 'soft' => '#ecfeff', 'border' => '#67e8f9', 'muted' => '#06b6d4', 'radius' => '4px', 'header' => 'digital', 'density' => 'compact'],
        ];
    }

    public static function fonts(): array
    {
        return [
            'system' => ['label' => 'Système', 'stack' => 'Inter, ui-sans-serif, system-ui, sans-serif'],
            'arial' => ['label' => 'Arial', 'stack' => 'Arial, Helvetica, sans-serif'],
            'serif' => ['label' => 'Serif', 'stack' => 'Georgia, Times New Roman, serif'],
            'georgia' => ['label' => 'Georgia', 'stack' => 'Georgia, serif'],
            'mono' => ['label' => 'Monospace', 'stack' => 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace'],
        ];
    }

    public static function fontStack(string $font): string
    {
        return self::fonts()[$font]['stack'] ?? self::fonts()['system']['stack'];
    }

    public static function receipt(): array
    {
        return [
            'template' => Setting::get('receipt_template', 'classic'),
            'code_type' => Setting::get('receipt_code_type', 'none'),
            'code_source' => Setting::get('receipt_code_source', 'reference'),
            'code_custom' => Setting::get('receipt_code_custom', ''),
            'font_family' => Setting::get('receipt_font_family', 'mono'),
            'font_size' => (int) Setting::get('receipt_font_size', 11),
            'font_weight' => (int) Setting::get('receipt_font_weight', 400),
            'paper_width' => (int) Setting::get('receipt_paper_width', 80),
        ];
    }

    public static function report(): array
    {
        $template = (string) Setting::get('report_template', 'executive');
        $template = array_key_exists($template, self::reportTemplates()) ? $template : 'executive';
        $font = (string) Setting::get('report_font_family', 'system');
        $font = array_key_exists($font, self::fonts()) ? $font : 'system';
        $weight = (int) Setting::get('report_font_weight', 400);

        return [
            'template' => $template,
            'template_meta' => self::reportTemplates()[$template],
            'theme' => self::reportThemes()[$template],
            'font_family' => $font,
            'font_size' => min(16, max(9, (int) Setting::get('report_font_size', 11))),
            'font_weight' => in_array($weight, [300, 400, 500, 600, 700, 800], true) ? $weight : 400,
        ];
    }

    /** Return the exact active theme, with a safe fallback for stale settings. */
    public static function reportTheme(?string $template = null): array
    {
        $template ??= self::report()['template'];

        return self::reportThemes()[$template] ?? self::reportThemes()['executive'];
    }

    /** Font families supported by Dompdf without an internet connection. */
    public static function pdfFontFamily(string $font): string
    {
        return match ($font) {
            'serif', 'georgia' => 'DejaVu Serif',
            'mono' => 'DejaVu Sans Mono',
            default => 'DejaVu Sans',
        };
    }

    /**
     * Common document identity injected into every server-generated report.
     * This is the single link between Configuration and the PDF renderer.
     */
    public static function reportDocumentContext(string $generatedBy = 'Système'): array
    {
        $preferences = self::report();
        $tenant = app(TenantContext::class);
        $tenantCompany = $tenant->company();
        $establishment = $tenant->establishment();
        $configuredLogo = trim((string) ($establishment?->logo_path ?: $tenantCompany?->logo_path ?: Setting::get('company_logo', '')));
        $configuredLogo = ltrim(str_replace('public/', '', $configuredLogo), '/');
        $logoPath = $configuredLogo !== '' && Storage::disk('public')->exists($configuredLogo)
            ? Storage::disk('public')->path($configuredLogo)
            : public_path('images/yeboox-logo.png');
        $logoMime = is_file($logoPath) ? (mime_content_type($logoPath) ?: 'image/png') : null;

        return [
            'preferences' => $preferences,
            'documentTheme' => $preferences['theme'],
            'reportTemplateMeta' => $preferences['template_meta'],
            'company' => [
                'name' => $tenantCompany?->name ?: Setting::get('company_name', 'Yeboox'),
                'address' => $tenantCompany?->address ?: Setting::get('company_address', 'Kinshasa, RDC'),
                'phone' => $tenantCompany?->phone ?: Setting::get('company_phone', ''),
                'email' => $tenantCompany?->email ?: Setting::get('company_email', ''),
                'rccm' => $tenantCompany?->rccm ?: Setting::get('company_rccm', ''),
                'nif' => $tenantCompany?->nif ?: Setting::get('company_nif', ''),
                'tva' => $tenantCompany?->tva_number ?: Setting::get('company_tva', ''),
            ],
            'establishment' => [
                'name' => $establishment?->name,
                'code' => $establishment?->code,
                'address' => $establishment?->address,
                'phone' => $establishment?->phone,
                'email' => $establishment?->email,
            ],
            'logo' => is_file($logoPath) ? 'data:'.$logoMime.';base64,'.base64_encode((string) file_get_contents($logoPath)) : null,
            'generatedBy' => $generatedBy,
            'generatedAt' => now(),
        ];
    }

    public static function inlineStyle(array $preferences): string
    {
        return sprintf(
            'font-family:%s;font-size:%dpx;font-weight:%d',
            self::fontStack((string) ($preferences['font_family'] ?? 'system')),
            (int) ($preferences['font_size'] ?? 11),
            (int) ($preferences['font_weight'] ?? 400),
        );
    }

    public static function resolveCodeValue(array $preferences, string $reference, string $company): string
    {
        return match ($preferences['code_source'] ?? 'reference') {
            'company' => $company,
            'custom' => trim((string) ($preferences['code_custom'] ?? '')) ?: $reference,
            default => $reference,
        };
    }
}
