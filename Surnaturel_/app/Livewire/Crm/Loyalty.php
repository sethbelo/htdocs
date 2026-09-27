<?php

namespace App\Livewire\Crm;

use App\Enums\PromotionType;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTier;
use App\Models\Promotion;
use App\Models\Reward;
use App\Models\Setting;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Fidélisation')]
class Loyalty extends Component
{
    use InteractsWithToasts;

    /** @var array<string, mixed> */
    public array $promotion = ['name' => '', 'code' => '', 'type' => 'percentage', 'value' => '', 'min_amount' => '0', 'starts_at' => '', 'ends_at' => ''];

    /** @var array<string, mixed> */
    public array $reward = ['name' => '', 'points_cost' => '', 'description' => ''];

    /** @var array<string, mixed> */
    public array $rules = [];

    public function mount(): void
    {
        $this->rules = [
            'amount_per_point' => (string) Setting::get('loyalty.amount_per_point', 1),
            'point_value' => (string) Setting::get('loyalty.point_value', 0.05),
            'card_validity_years' => (string) Setting::get('loyalty.card_validity_years', ''),
        ];
    }

    public function saveRules(): void
    {
        $this->authorize('loyalty.update');
        $this->validate(['rules.amount_per_point' => ['required', 'numeric', 'gt:0'], 'rules.point_value' => ['required', 'numeric', 'min:0'], 'rules.card_validity_years' => ['nullable', 'integer', 'min:1']]);

        foreach ($this->rules as $key => $value) {
            Setting::put("loyalty.{$key}", $value === '' ? null : (float) $value);
        }

        $this->toast('Règles de fidélité enregistrées.');
    }

    public function addPromotion(): void
    {
        $this->authorize('loyalty.create');
        $data = $this->validate([
            'promotion.name' => ['required', 'string', 'max:255'],
            'promotion.code' => ['nullable', 'string', 'max:30', 'unique:promotions,code'],
            'promotion.type' => ['required', Rule::enum(PromotionType::class)],
            'promotion.value' => ['required', 'numeric', 'gt:0'],
            'promotion.min_amount' => ['nullable', 'numeric', 'min:0'],
            'promotion.starts_at' => ['nullable', 'date'],
            'promotion.ends_at' => ['nullable', 'date', 'after_or_equal:promotion.starts_at'],
        ])['promotion'];

        Promotion::query()->create(array_map(fn ($v) => $v === '' ? null : $v, $data) + ['min_amount' => 0]);
        $this->reset('promotion');
        $this->toast('Promotion créée.');
    }

    public function togglePromotion(int $id): void
    {
        $this->authorize('loyalty.update');
        $promotion = Promotion::query()->findOrFail($id);
        $promotion->update(['is_active' => ! $promotion->is_active]);
    }

    public function addReward(): void
    {
        $this->authorize('loyalty.create');
        $data = $this->validate(['reward.name' => ['required', 'string', 'max:255'], 'reward.points_cost' => ['required', 'integer', 'min:1'], 'reward.description' => ['nullable', 'string']])['reward'];
        Reward::query()->create($data);
        $this->reset('reward');
        $this->toast('Récompense ajoutée.');
    }

    public function render()
    {
        return view('livewire.crm.loyalty', [
            'tiers' => LoyaltyTier::query()->orderBy('min_points')->withCount('accounts')->get(),
            'promotions' => Promotion::query()->latest()->get(),
            'rewards' => Reward::query()->orderBy('points_cost')->get(),
            'topAccounts' => LoyaltyAccount::query()->with('client', 'tier')->orderByDesc('lifetime_points')->limit(10)->get(),
            'types' => PromotionType::options(),
        ]);
    }
}
