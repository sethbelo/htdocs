<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\DocumentPreferences;
use App\Support\ReportWordGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

class DocumentModelExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_catalog_contains_fourteen_complete_pdf_and_word_models(): void
    {
        $templates = DocumentPreferences::reportTemplates();
        $themes = DocumentPreferences::reportThemes();

        $this->assertCount(14, $templates);
        $this->assertSame(array_keys($templates), array_keys($themes));
        $this->assertSame(['official', 'heritage', 'sahara', 'digital'], array_slice(array_keys($templates), -4));

        foreach ($themes as $theme) {
            $this->assertArrayHasKey('accent', $theme);
            $this->assertArrayHasKey('dark', $theme);
            $this->assertArrayHasKey('soft', $theme);
            $this->assertArrayHasKey('header', $theme);
        }
    }

    public function test_management_pdf_uses_the_selected_model_and_structured_document_view(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole(Role::create(['name' => 'Gérant']));
        Setting::set('report_template', 'official');

        $response = $this->actingAs($manager)->get(route('reports.pdf', [
            'type' => 'sessions',
            'start' => now()->startOfMonth()->format('Y-m-d'),
            'end' => now()->format('Y-m-d'),
        ]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Yeboox-Document-Template', 'official');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_word_export_is_a_real_docx_with_toc_logo_header_footer_and_tables(): void
    {
        Setting::set('report_template', 'digital');
        $binary = app(ReportWordGenerator::class)->generate(
            'sales',
            ['sales' => collect()],
            '2026-08-01',
            '2026-08-31',
            'Agent test',
        );

        $this->assertStringStartsWith('PK', $binary);
        $path = tempnam(storage_path('app'), 'docx-test-');
        file_put_contents($path, $binary);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);
        $document = (string) $zip->getFromName('word/document.xml');
        $header = (string) $zip->getFromName('word/header1.xml');
        $footer = (string) $zip->getFromName('word/footer1.xml');

        $this->assertStringContainsString('TABLE DES MATIÈRES', $document);
        $this->assertStringContainsString('Synthèse des encaissements', $document);
        $this->assertStringContainsString('<w:tbl>', $document);
        $this->assertStringContainsString('rIdLogo', $header);
        $this->assertStringContainsString('NUMPAGES', $footer);
        $this->assertNotFalse($zip->locateName('word/media/logo.png'));

        $zip->close();
        unlink($path);
    }
}
