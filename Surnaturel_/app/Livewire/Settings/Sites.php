<?php

namespace App\Livewire\Settings;

use App\Enums\SiteType;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\CashRegister;
use App\Models\PrintRoom;
use App\Models\Site;
use App\Models\TreasuryAccount;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Paramètres — Structure')]
class Sites extends Component
{
    use InteractsWithToasts;

    public string $entity = 'site';

    public ?int $editingId = null;

    /** @var array<string, mixed> */
    public array $form = [];

    /**
     * @var array<string, class-string<Model>>
     */
    private const MODELS = ['site' => Site::class, 'warehouse' => Warehouse::class, 'register' => CashRegister::class, 'room' => PrintRoom::class];

    public function edit(string $entity, ?int $id = null): void
    {
        $this->authorize($id ? 'sites.update' : 'sites.create');
        $this->entity = $entity;
        $this->editingId = $id;
        $model = $id ? (self::MODELS[$entity])::query()->findOrFail($id) : null;

        $this->form = match ($entity) {
            'site' => $model ? $model->only(['code', 'name', 'parent_id', 'address', 'city', 'phone', 'email', 'latitude', 'longitude', 'main_warehouse_id', 'is_active']) + ['type' => $model->type->value] : ['code' => '', 'name' => '', 'type' => 'shop', 'parent_id' => '', 'address' => '', 'city' => '', 'phone' => '', 'email' => '', 'latitude' => '', 'longitude' => '', 'main_warehouse_id' => '', 'is_active' => true],
            'warehouse' => $model?->only(['code', 'name', 'site_id', 'manager_id', 'address', 'city', 'phone', 'latitude', 'longitude', 'is_active']) ?? ['code' => '', 'name' => '', 'site_id' => '', 'manager_id' => '', 'address' => '', 'city' => '', 'phone' => '', 'latitude' => '', 'longitude' => '', 'is_active' => true],
            'register' => $model?->only(['code', 'name', 'site_id', 'warehouse_id', 'treasury_account_id', 'requires_pin', 'max_disbursement_without_approval', 'is_active']) ?? ['code' => '', 'name' => '', 'site_id' => '', 'warehouse_id' => '', 'treasury_account_id' => '', 'requires_pin' => true, 'max_disbursement_without_approval' => 0, 'is_active' => true],
            'room' => $model?->only(['code', 'name', 'site_id', 'manager_id', 'is_active']) ?? ['code' => '', 'name' => '', 'site_id' => '', 'manager_id' => '', 'is_active' => true],
        };

        $this->modal('entity')->show();
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'sites.update' : 'sites.create');
        $model = self::MODELS[$this->entity];
        $table = (new $model)->getTable();

        $rules = ['form.code' => ['required', 'string', 'max:20', Rule::unique($table, 'code')->ignore($this->editingId)], 'form.name' => ['required', 'string', 'max:255'], 'form.is_active' => ['boolean']];

        $rules += match ($this->entity) {
            'site' => ['form.type' => ['required', Rule::enum(SiteType::class)], 'form.parent_id' => ['nullable', 'exists:sites,id'], 'form.address' => ['nullable', 'string'], 'form.city' => ['nullable', 'string'], 'form.phone' => ['nullable', 'string'], 'form.email' => ['nullable', 'email'], 'form.latitude' => ['nullable', 'numeric', 'between:-90,90'], 'form.longitude' => ['nullable', 'numeric', 'between:-180,180'], 'form.main_warehouse_id' => ['nullable', 'exists:warehouses,id']],
            'warehouse' => ['form.site_id' => ['required', 'exists:sites,id'], 'form.manager_id' => ['nullable', 'exists:users,id'], 'form.address' => ['nullable', 'string'], 'form.city' => ['nullable', 'string'], 'form.phone' => ['nullable', 'string'], 'form.latitude' => ['nullable', 'numeric', 'between:-90,90'], 'form.longitude' => ['nullable', 'numeric', 'between:-180,180']],
            'register' => ['form.site_id' => ['required', 'exists:sites,id'], 'form.warehouse_id' => ['required', 'exists:warehouses,id'], 'form.treasury_account_id' => ['nullable', 'exists:treasury_accounts,id'], 'form.requires_pin' => ['boolean'], 'form.max_disbursement_without_approval' => ['required', 'numeric', 'min:0']],
            'room' => ['form.site_id' => ['required', 'exists:sites,id'], 'form.manager_id' => ['nullable', 'exists:users,id']],
        };

        $data = array_map(fn ($v) => $v === '' ? null : $v, $this->validate($rules)['form']);
        $model::query()->updateOrCreate(['id' => $this->editingId], $data);

        $this->modal('entity')->close();
        $this->toast('Enregistré.');
    }

    public function render()
    {
        return view('livewire.settings.sites', [
            'sites' => Site::query()->with('parent', 'mainWarehouse')->withCount('users')->orderBy('type')->orderBy('name')->get(),
            'warehouses' => Warehouse::query()->with('site', 'manager')->orderBy('name')->get(),
            'registers' => CashRegister::query()->with('site', 'warehouse', 'treasuryAccount', 'openSession.cashier')->orderBy('code')->get(),
            'rooms' => PrintRoom::query()->with('site', 'manager')->get(),
            'treasuryAccounts' => TreasuryAccount::query()->where('type', 'cash')->get(),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'types' => SiteType::options(),
        ]);
    }
}
