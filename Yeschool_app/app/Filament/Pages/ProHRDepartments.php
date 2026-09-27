<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Department;
use App\Models\Teacher;
use Illuminate\Support\Facades\Auth;

class ProHRDepartments extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-folder-open';
    protected static string $layout = 'layouts.sigs-layout';
    protected static string $view = 'filament.pages.pro-hr-departments';
    protected static ?string $title = 'Gestion des Départements';

    protected static bool $shouldRegisterNavigation = false;

    public $departmentsList = [];
    public $personnelList = [];

    // Form Fields
    public $isDepartmentOffcanvasOpen = false;
    public $department_id;
    public $form_name;
    public $form_description;
    public $form_manager_id;

    // Delete Confirmation State
    public $isConfirmDeleteModalOpen = false;
    public $deletingId;

    public function mount()
    {
        abort_unless(Auth::user() && Auth::user()->hasAnyRole(['Super Admin', 'RH', 'Responsable RH', 'Directeur General', 'Directeur Général']), 403, 'Accès refusé.');
        
        // Auto-seed default departments if table is empty
        $this->ensureDefaultDepartments();

        $this->loadData();
    }

    public function ensureDefaultDepartments()
    {
        $defaults = ['Enseignant', 'Comptable', 'RH', 'Administration', 'Nettoyage', 'Sécurité'];
        foreach ($defaults as $name) {
            Department::firstOrCreate([
                'name' => $name
            ], [
                'description' => "Département regroupant le personnel de type {$name}."
            ]);
        }
    }

    public function loadData()
    {
        $this->departmentsList = Department::with(['manager', 'teachers'])
            ->withCount('teachers')
            ->orderBy('name')
            ->get()
            ->toArray();

        $this->personnelList = Teacher::where('status', 'Actif')
            ->orderBy('last_name')
            ->get()
            ->toArray();
    }

    public function createDepartment()
    {
        $this->resetValidation();
        $this->reset(['department_id', 'form_name', 'form_description', 'form_manager_id']);
        $this->isDepartmentOffcanvasOpen = true;
    }

    public function editDepartment($id)
    {
        $this->resetValidation();
        $d = Department::findOrFail($id);

        $this->department_id = $d->id;
        $this->form_name = $d->name;
        $this->form_description = $d->description;
        $this->form_manager_id = $d->manager_id;

        $this->isDepartmentOffcanvasOpen = true;
    }

    public function saveDepartment()
    {
        $this->validate([
            'form_name' => 'required|string|max:255|unique:departments,name,' . $this->department_id,
            'form_description' => 'nullable|string',
            'form_manager_id' => 'nullable|exists:teachers,id',
        ]);

        Department::updateOrCreate(
            ['id' => $this->department_id],
            [
                'name' => $this->form_name,
                'description' => $this->form_description,
                'manager_id' => $this->form_manager_id ?: null,
            ]
        );

        $this->isDepartmentOffcanvasOpen = false;
        $this->loadData();
        session()->flash('success', 'Département enregistré avec succès.');
    }

    public function promptDelete($id)
    {
        $this->deletingId = $id;
        $this->isConfirmDeleteModalOpen = true;
    }

    public function deleteDepartment()
    {
        if ($this->deletingId) {
            Department::destroy($this->deletingId);
            $this->deletingId = null;
            $this->isConfirmDeleteModalOpen = false;
            $this->loadData();
            session()->flash('success', 'Département supprimé avec succès.');
        }
    }
}
