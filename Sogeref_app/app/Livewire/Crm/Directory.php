<?php

namespace App\Livewire\Crm;

use App\Enums\ContactType;
use App\Livewire\Concerns\InteractsWithUi;
use App\Livewire\Crm\Concerns\EditsContacts;
use App\Models\Contact;
use App\Models\Department;
use App\Models\Employee;
use App\Services\ContactService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Annuaire : tous les contacts (clients, prospects, fournisseurs,
 * partenaires) par ordre alphabétique avec barre A–Z, filtres, liens
 * d'appel / WhatsApp / e-mail, vCard et export ; onglet « Annuaire
 * interne » (collaborateurs : poste, service, téléphone, e-mail).
 */
#[Title('Annuaire')]
class Directory extends Component
{
    use EditsContacts, InteractsWithUi, WithPagination;

    /** Expression SQL de la lettre de classement (portable SQLite / MySQL / PostgreSQL). */
    public const LETTER_SQL = "UPPER(SUBSTR(COALESCE(NULLIF(company_name, ''), NULLIF(last_name, ''), NULLIF(first_name, ''), reference), 1, 1))";

    #[Url(as: 'vue')]
    public string $view = 'contacts';

    #[Url(as: 'lettre')]
    public string $letter = '';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'type')]
    public string $type = '';

    #[Url(as: 'segment')]
    public string $segment = '';

    #[Url(as: 'ville')]
    public string $city = '';

    #[Url(as: 'service')]
    public string $department = '';

    public function updating($name): void
    {
        if (in_array($name, ['view', 'letter', 'search', 'type', 'segment', 'city', 'department'], true)) {
            $this->resetPage();
        }
    }

    public function filters(): array
    {
        return array_filter(['lettre' => $this->letter, 'q' => $this->search, 'type' => $this->type, 'segment' => $this->segment, 'ville' => $this->city]);
    }

    /** Requête de l'annuaire (partagée avec l'export Excel et l'impression). */
    public static function directoryQuery(int $companyId, array $f, bool $withLetter = true): Builder
    {
        $letter = mb_strtoupper((string) ($f['lettre'] ?? ''));

        return Contact::query()->where('company_id', $companyId)->where('is_active', true)
            ->when(in_array($f['type'] ?? '', array_keys(ContactType::options()), true), fn ($q) => $q->where('type', $f['type']))
            ->when(in_array($f['segment'] ?? '', ['b2b', 'b2c'], true), fn ($q) => $q->where('segment', $f['segment']))
            ->when($f['ville'] ?? '', fn ($q, $c) => $q->where('city', $c))
            ->when($withLetter && preg_match('/^[A-Z]$/', $letter), fn ($q) => $q->whereRaw(self::LETTER_SQL.' = ?', [$letter]))
            ->when($withLetter && $letter === '#', fn ($q) => $q->whereRaw(self::LETTER_SQL." NOT BETWEEN 'A' AND 'Z'"))
            ->when($f['q'] ?? '', fn ($q, $s) => $q->where(fn ($q) => $q->where('company_name', 'like', "%{$s}%")->orWhere('first_name', 'like', "%{$s}%")->orWhere('last_name', 'like', "%{$s}%")
                ->orWhere('phone', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")->orWhere('reference', 'like', "%{$s}%")->orWhere('city', 'like', "%{$s}%")))
            ->orderByRaw("LOWER(COALESCE(NULLIF(company_name, ''), NULLIF(last_name, ''), first_name))")->orderBy('first_name');
    }

    public function render()
    {
        $cid = company_id();
        $internal = $this->view === 'interne';

        $data = [
            'internal' => $internal,
            'types' => ContactType::options(),
            'cities' => Contact::where('company_id', $cid)->whereNotNull('city')->where('city', '!=', '')->distinct()->orderBy('city')->pluck('city'),
            'hasWhatsapp' => ContactService::hasColumn('whatsapp'),
        ];

        if ($internal) {
            $data['employees'] = Employee::with('department', 'position')->where('company_id', $cid)->where('status', 'active')
                ->when(ctype_digit($this->department), fn ($q) => $q->where('department_id', (int) $this->department))
                ->when(preg_match('/^[A-Z]$/', $this->letter), fn ($q) => $q->whereRaw('UPPER(SUBSTR(last_name, 1, 1)) = ?', [$this->letter]))
                ->when($this->search, fn ($q, $s) => $q->where(fn ($q) => $q->where('first_name', 'like', "%{$s}%")->orWhere('last_name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")))
                ->orderBy('last_name')->orderBy('first_name')->paginate(48);
            $data['departments'] = Department::where('company_id', $cid)->orderBy('name')->get();
            $data['letters'] = Employee::where('company_id', $cid)->where('status', 'active')->selectRaw('UPPER(SUBSTR(last_name, 1, 1)) as l, count(*) as c')->groupBy('l')->pluck('c', 'l');
        } else {
            $data['contacts'] = self::directoryQuery($cid, $this->filters())->paginate(48);
            $data['letters'] = self::directoryQuery($cid, $this->filters(), false)->reorder()->selectRaw(self::LETTER_SQL.' as l, count(*) as c')->groupBy('l')->pluck('c', 'l');
        }

        return view('livewire.crm.directory', $data);
    }
}
