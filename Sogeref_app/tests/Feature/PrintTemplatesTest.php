<?php

namespace Tests\Feature;

use App\Livewire\Sales\DocumentEditor;
use App\Livewire\System\Master;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Payment;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Modèles d'impression : 5 modèles par catégorie (devis, pro forma, factures,
 * reçus, bons de sortie), aperçu de chacun, choix par le Master et intitulé
 * « Devis » / « Pro forma » à la création d'un devis.
 */
class PrintTemplatesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function as(string $login): User
    {
        $user = User::where('email', "{$login}@sogeref.cd")->firstOrFail();
        $this->actingAs($user);

        return $user;
    }

    public function test_each_category_offers_at_least_five_templates(): void
    {
        foreach (Master::templateCatalog() as $kind => $category) {
            $this->assertGreaterThanOrEqual(5, count($category['templates']), "Catégorie {$kind}");
        }
    }

    public function test_every_template_preview_renders_a_pdf(): void
    {
        $this->as('master');
        foreach (Master::templateCatalog() as $kind => $category) {
            foreach (array_keys($category['templates']) as $template) {
                $response = $this->get(route('pdf.preview', ['kind' => $kind, 'template' => $template]));
                $response->assertOk();
                $this->assertSame('application/pdf', $response->headers->get('Content-Type'), "{$kind}/{$template}");
            }
        }
    }

    public function test_receipt_and_exit_voucher_print_with_each_template(): void
    {
        $this->as('master');
        $payment = Payment::has('allocations')->firstOrFail();
        foreach (array_keys(config('erp.print_templates.receipt')) as $template) {
            $this->get(route('pdf.receipt', [$payment, 'modele' => $template]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        }
        $voucher = Voucher::where('type', 'sortie')->firstOrFail();
        foreach (array_keys(config('erp.print_templates.exit_voucher')) as $template) {
            $this->get(route('pdf.voucher', [$voucher, 'modele' => $template]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        }
        $this->assertDoesNotMatchRegularExpression('#/pdf/recu/\d+$#', route('pdf.receipt', $payment));
    }

    public function test_receipt_requires_cash_or_invoices_access(): void
    {
        $this->as('rh');
        $this->get(route('pdf.receipt', Payment::firstOrFail()))->assertForbidden();
        $this->get(route('pdf.preview', ['kind' => 'receipt', 'template' => 'moderne']))->assertForbidden();
    }

    public function test_master_chooses_defaults_and_previews(): void
    {
        $this->as('master');
        Livewire::test(Master::class)->set('tab', 'documents')
            ->call('previewTemplate', 'receipt', 'carnet')->assertSet('preview.1', 'carnet')
            ->assertSee(route('pdf.preview', ['kind' => 'receipt', 'template' => 'carnet']), false)
            ->set('templates.receipt', 'carnet')->set('templates.exit_voucher', 'magasin')->set('templates.proforma', 'prestige')
            ->call('saveDocuments')->assertHasNoErrors();
        $this->assertSame('carnet', erp_setting('templates.receipt'));
        $this->assertSame('magasin', erp_setting('templates.exit_voucher'));

        Livewire::test(Master::class)->set('templates.receipt', 'inexistant')->call('saveDocuments')->assertHasErrors('templates.receipt');
    }

    public function test_new_quote_can_be_titled_proforma(): void
    {
        $this->as('master');
        $contact = Contact::where('type', 'client')->firstOrFail();

        $editor = Livewire::withQueryParams(['intitule' => 'proforma'])->test(DocumentEditor::class, ['type' => 'quote'])
            ->assertSet('heading', 'proforma')->assertSee('Nouvelle pro forma')
            ->assertSet('template', erp_setting('templates.proforma', 'prestige'))
            ->set('contact_id', $contact->id)->set('openOrder', false)
            ->set('items.0.description', 'Portes aluminium')->set('items.0.unit_price', 250)
            ->call('save')->assertHasNoErrors();

        $document = Document::where('type', 'quote')->latest('id')->firstOrFail();
        $this->assertTrue($document->isProformaHeading());
        $this->get(route('documents.show', $document))->assertOk()->assertSee('Pro forma');
        $this->get(route('pdf.document', $document))->assertOk();

        request()->query->remove('intitule');
        Livewire::withQueryParams([])->test(DocumentEditor::class, ['type' => 'quote'])->assertSet('heading', 'devis')
            ->set('heading', 'proforma')->assertSet('template', erp_setting('templates.proforma', 'prestige'))
            ->set('heading', 'devis')->assertSet('template', erp_setting('templates.quote', 'moderne'));
        $this->get(route('quotes'))->assertOk()->assertSee('Nouvelle pro forma');
    }
}
