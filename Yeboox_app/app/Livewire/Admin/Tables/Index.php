<?php

namespace App\Livewire\Admin\Tables;

use App\Models\Table;
use App\Models\Zone;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $selectedZone = '';

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public ?string $editingId = null;

    public ?string $deleteId = null;

    public string $deleteName = '';

    public string $zone_id = '';

    public string $name = '';

    public string $number = '';

    public int $capacity = 4;

    public string $shape = 'square';

    public bool $is_active = true;

    public function updatedSelectedZone(): void
    {
        $this->resetPage();
    }

    public function updateStatus(string $tableId, string $status): void
    {
        abort_unless(auth()->user()->can('tables.manage'), 403);
        abort_unless(in_array($status, ['free', 'occupied', 'reserved', 'cleaning'], true), 422);
        $table = Table::findOrFail($tableId);
        $table->update(['status' => $status]);
    }

    public function openForm(?string $id = null): void
    {
        abort_unless(auth()->user()->can('tables.manage'), 403);
        $this->resetForm();
        if ($id) {
            $table = Table::findOrFail($id);
            $this->editingId = $table->id;
            $this->zone_id = $table->zone_id ?? '';
            $this->name = $table->name;
            $this->number = $table->number;
            $this->capacity = $table->capacity;
            $this->shape = $table->shape;
            $this->is_active = $table->is_active;
        }
        $this->showFormModal = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('tables.manage'), 403);
        $establishmentId = auth()->user()->establishment_id;
        $validated = $this->validate([
            'zone_id' => ['nullable', Rule::exists('zones', 'id')->where('establishment_id', $establishmentId)],
            'name' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:20', Rule::unique('tables', 'number')->where('establishment_id', $establishmentId)->ignore($this->editingId)],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'shape' => ['required', 'in:square,round,rectangle'],
            'is_active' => ['boolean'],
        ]);
        $validated['zone_id'] = $validated['zone_id'] ?: null;
        $validated['establishment_id'] = $establishmentId;
        $validated['branch_id'] = auth()->user()->branch_id;

        $table = $this->editingId ? Table::findOrFail($this->editingId) : new Table;
        if (! $this->editingId) {
            $validated['status'] = 'free';
        }
        $table->fill($validated)->save();
        $this->showFormModal = false;
        session()->flash('success', $this->editingId ? 'Table modifiée.' : 'Table créée.');
        $this->resetForm();
    }

    public function requestDelete(string $id): void
    {
        abort_unless(auth()->user()->can('tables.manage'), 403);
        $table = Table::findOrFail($id);
        $this->deleteId = $table->id;
        $this->deleteName = $table->name;
        $this->showDeleteModal = true;
    }

    public function confirmDelete(): void
    {
        abort_unless(auth()->user()->can('tables.manage'), 403);
        $table = Table::withCount(['reservations' => fn ($query) => $query->whereDate('reservation_date', '>=', today())->whereNotIn('status', ['cancelled', 'completed', 'no_show'])])->findOrFail($this->deleteId);
        if ($table->reservations_count > 0) {
            $this->addError('delete', 'Cette table possède encore des réservations à venir.');

            return;
        }
        $table->delete();
        $this->reset(['showDeleteModal', 'deleteId', 'deleteName']);
        session()->flash('success', 'Table supprimée.');
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'zone_id', 'name', 'number']);
        $this->capacity = 4;
        $this->shape = 'square';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render()
    {
        $establishmentId = auth()->user()->establishment_id;

        $zones = Zone::where('establishment_id', $establishmentId)
            ->active()->ordered()
            ->withCount('tables')
            ->get();

        $tablesQuery = Table::where('establishment_id', $establishmentId)
            ->with('zone')
            ->active()
            ->when($this->selectedZone, fn ($q) => $q->where('zone_id', $this->selectedZone))
            ->orderBy('sort_order');

        $stats = [
            'total' => (clone $tablesQuery)->count(),
            'free' => (clone $tablesQuery)->where('status', 'free')->count(),
            'occupied' => (clone $tablesQuery)->where('status', 'occupied')->count(),
            'reserved' => (clone $tablesQuery)->where('status', 'reserved')->count(),
        ];

        $tables = $tablesQuery->paginate(24);

        return view('livewire.admin.tables.index', compact('zones', 'tables', 'stats'))
            ->layout('layouts.admin', ['title' => 'Plan de salle', 'breadcrumb' => 'Tables']);
    }
}
