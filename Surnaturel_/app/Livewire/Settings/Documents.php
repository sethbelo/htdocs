<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Setting;
use App\Support\DocumentSignatures;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Personnalisation des documents : mentions légales du pied de page, signataires de chaque bon, modèles de messages.
 */
#[Title('Paramètres — Documents & signatures')]
class Documents extends Component
{
    use InteractsWithToasts;

    /** @var array<string, string> Signataires, un par ligne, par type de document. */
    public array $signatures = [];

    public string $slogan = '';

    public string $bankDetails = '';

    public string $ticketFooter = '';

    public int $cardValidityYears = 2;

    /** @var list<array{name: string, body: string}> */
    public array $templates = [];

    public function mount(): void
    {
        $this->signatures = collect(DocumentSignatures::all())->map(fn (array $doc): string => implode("\n", $doc['signatories']))->all();
        $this->slogan = (string) Setting::get('company.slogan', '');
        $this->bankDetails = (string) Setting::get('company.bank_details', '');
        $this->ticketFooter = (string) Setting::get('company.ticket_footer', '');
        $this->cardValidityYears = (int) Setting::get('hr.card_validity_years', 2);
        $this->templates = array_values(Setting::get('marketing.whatsapp_templates', []));
    }

    public function addTemplate(): void
    {
        $this->templates[] = ['name' => '', 'body' => ''];
    }

    public function removeTemplate(int $index): void
    {
        unset($this->templates[$index]);
        $this->templates = array_values($this->templates);
    }

    public function resetSignatures(string $type): void
    {
        $this->signatures[$type] = implode("\n", DocumentSignatures::DEFAULTS[$type]['signatories'] ?? []);
    }

    public function save(): void
    {
        $this->authorize('settings.update');
        $this->validate([
            'signatures.*' => ['nullable', 'string', 'max:500'],
            'slogan' => ['nullable', 'string', 'max:120'],
            'bankDetails' => ['nullable', 'string', 'max:300'],
            'ticketFooter' => ['nullable', 'string', 'max:200'],
            'cardValidityYears' => ['required', 'integer', 'between:1,10'],
            'templates.*.name' => ['required', 'string', 'max:60'],
            'templates.*.body' => ['required', 'string', 'max:1000'],
        ], [], ['templates.*.name' => 'nom du modèle', 'templates.*.body' => 'message']);

        $signatures = collect(DocumentSignatures::DEFAULTS)->map(fn (array $default, string $type): array => [
            'signatories' => collect(preg_split('/\R/', $this->signatures[$type] ?? ''))->map(fn ($line) => trim($line))->filter()->take(5)->values()->all(),
        ])->all();

        Setting::put('documents.signatures', $signatures);
        Setting::put('company.slogan', $this->slogan ?: null);
        Setting::put('company.bank_details', $this->bankDetails ?: null);
        Setting::put('company.ticket_footer', $this->ticketFooter ?: null);
        Setting::put('hr.card_validity_years', $this->cardValidityYears);
        Setting::put('marketing.whatsapp_templates', array_values($this->templates));

        $this->toast('Paramètres des documents enregistrés.');
    }

    public function render()
    {
        return view('livewire.settings.documents', ['types' => DocumentSignatures::DEFAULTS]);
    }
}
