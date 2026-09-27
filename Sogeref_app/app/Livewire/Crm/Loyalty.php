<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\InteractsWithUi;
use App\Models\LoyaltyCard;
use App\Models\LoyaltyProgram;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Programmes de fidélité B2C / B2B : règles de cumul, paliers, cartes.
 */
#[Title('Fidélité')]
class Loyalty extends Component
{
    use InteractsWithUi, WithPagination;

    public string $search = '';

    public bool $showProgram = false;

    public ?int $editing = null;

    public array $program = [];

    public ?int $adjustCard = null;

    public string $adjustPoints = '';

    public string $adjustNote = '';

    public function editProgram(?int $id = null): void
    {
        $this->authorizeModule('loyalty', 'manage');
        $p = $id ? LoyaltyProgram::where('company_id', company_id())->findOrFail($id) : null;
        $this->editing = $p?->id;
        $this->program = [
            'name' => $p?->name ?? '', 'segment' => $p?->segment ?? 'b2c', 'points_per_unit' => (float) ($p?->points_per_unit ?? 1), 'point_value' => (float) ($p?->point_value ?? 0.05),
            'min_redeem_points' => $p?->min_redeem_points ?? 100, 'is_active' => $p?->is_active ?? true,
            'tiers' => $p?->tiers ?? [['name' => 'Bronze', 'threshold' => 0, 'discount' => 0], ['name' => 'Argent', 'threshold' => 2000, 'discount' => 3], ['name' => 'Or', 'threshold' => 8000, 'discount' => 5]],
        ];
        $this->showProgram = true;
    }

    public function addTier(): void
    {
        $this->program['tiers'][] = ['name' => '', 'threshold' => 0, 'discount' => 0];
    }

    public function removeTier(int $i): void
    {
        unset($this->program['tiers'][$i]);
        $this->program['tiers'] = array_values($this->program['tiers']);
    }

    public function saveProgram(): void
    {
        $this->authorizeModule('loyalty', 'manage');
        $data = $this->validate([
            'program.name' => ['required', 'string', 'max:120'], 'program.segment' => ['required', 'in:all,b2b,b2c'], 'program.points_per_unit' => ['required', 'numeric', 'min:0'],
            'program.point_value' => ['required', 'numeric', 'min:0'], 'program.min_redeem_points' => ['required', 'integer', 'min:0'], 'program.is_active' => ['boolean'],
            'program.tiers' => ['array'], 'program.tiers.*.name' => ['required', 'string', 'max:40'], 'program.tiers.*.threshold' => ['required', 'numeric', 'min:0'], 'program.tiers.*.discount' => ['required', 'numeric', 'between:0,50'],
        ], [], ['program.name' => 'nom'])['program'];
        $data['tiers'] = collect($data['tiers'])->sortBy('threshold')->values()->all();
        LoyaltyProgram::updateOrCreate(['id' => $this->editing], $data + ['company_id' => company_id()]);
        $this->showProgram = false;
        $this->toast('Programme enregistré.');
    }

    public function adjust(): void
    {
        $this->authorizeModule('loyalty', 'manage');
        $this->validate(['adjustPoints' => ['required', 'integer', 'not_in:0'], 'adjustNote' => ['required', 'string', 'max:160']], [], ['adjustPoints' => 'points', 'adjustNote' => 'motif']);
        $card = LoyaltyCard::where('company_id', company_id())->findOrFail($this->adjustCard);
        $card->increment('points', (int) $this->adjustPoints);
        $card->transactions()->create(['type' => 'adjust', 'points' => (int) $this->adjustPoints, 'note' => $this->adjustNote]);
        $this->reset(['adjustCard', 'adjustPoints', 'adjustNote']);
        $this->toast('Solde de points ajusté.');
    }

    public function render()
    {
        $cid = company_id();

        return view('livewire.crm.loyalty', [
            'programs' => LoyaltyProgram::withCount('cards')->withSum('cards', 'points')->where('company_id', $cid)->get(),
            'cards' => LoyaltyCard::with('contact', 'program')->where('company_id', $cid)
                ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('card_number', 'like', "%{$this->search}%")->orWhereHas('contact', fn ($c) => $c->where('company_name', 'like', "%{$this->search}%")->orWhere('last_name', 'like', "%{$this->search}%"))))
                ->orderByDesc('total_spent')->paginate(15),
        ]);
    }
}
