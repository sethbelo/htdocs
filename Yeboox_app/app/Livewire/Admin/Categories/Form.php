<?php

namespace App\Livewire\Admin\Categories;

use App\Models\Category;
use Livewire\Component;

class Form extends Component
{
    public ?Category $category = null;

    public bool $editing = false;

    public string $name = '';

    public string $description = '';

    public string $parent_id = '';

    public string $color = '#3B82F6';

    public string $icon = '';

    public int $sort_order = 0;

    public bool $is_active = true;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'parent_id' => 'nullable|exists:categories,id',
            'color' => 'required|string|max:20',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ];
    }

    public function mount(?string $categoryId = null): void
    {
        if ($categoryId) {
            $this->category = Category::findOrFail($categoryId);
            $this->editing = true;
            $this->fill($this->category->only(['name', 'description', 'color', 'icon', 'sort_order', 'is_active']));
            $this->parent_id = $this->category->parent_id ?? '';
        }
    }

    public function save(): void
    {
        $validated = $this->validate();
        $validated['parent_id'] = $validated['parent_id'] ?: null;

        if ($this->editing) {
            $this->category->update($validated);
            session()->flash('success', 'Catégorie modifiée.');
        } else {
            $validated['establishment_id'] = auth()->user()->establishment_id;
            Category::create($validated);
            session()->flash('success', 'Catégorie créée.');
        }

        $this->redirect(route('admin.categories.index'), navigate: true);
    }

    public function render()
    {
        $parentCategories = Category::where('establishment_id', auth()->user()->establishment_id)
            ->whereNull('parent_id')
            ->when($this->editing, fn ($q) => $q->where('id', '!=', $this->category->id))
            ->ordered()->get();

        return view('livewire.admin.categories.form', compact('parentCategories'))
            ->layout('layouts.admin', ['title' => $this->editing ? 'Modifier la catégorie' : 'Nouvelle catégorie']);
    }
}
