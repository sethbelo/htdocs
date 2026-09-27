<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Teacher;
use App\Models\HrConflict;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ProHRConflicts extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static string $layout = 'layouts.sigs-layout';
    protected static string $view = 'filament.pages.pro-hr-conflicts';
    protected static ?string $title = 'Gestion des Conflits';

    protected static bool $shouldRegisterNavigation = false;

    public $conflictsList = [];
    public $personnelList = [];

    // Filters
    public $filter_status = 'all';
    public $filter_severity = 'all';

    // Form Fields
    public $isConflictOffcanvasOpen = false;
    public $conflict_id;
    public $form_employee_involved_id;
    public $form_title;
    public $form_description;
    public $form_date_reported;
    public $form_severity = 'Moyenne';
    public $form_status = 'En cours';
    public $form_resolution_notes;

    // Delete Confirmation State
    public $isConfirmDeleteModalOpen = false;
    public $deletingId;

    public function mount()
    {
        abort_unless(Auth::user() && Auth::user()->hasAnyRole(['Super Admin', 'RH', 'Responsable RH', 'Directeur General', 'Directeur Général']), 403, 'Accès refusé.');
        $this->loadData();
    }

    public function loadData()
    {
        $query = HrConflict::with(['employee', 'reporter']);

        if ($this->filter_status !== 'all') {
            $query->where('status', $this->filter_status);
        }

        if ($this->filter_severity !== 'all') {
            $query->where('severity', $this->filter_severity);
        }

        $this->conflictsList = $query->orderByDesc('date_reported')->get()->toArray();
        $this->personnelList = Teacher::where('status', 'Actif')->orderBy('last_name')->get()->toArray();
    }

    public function updatedFilterStatus()
    {
        $this->loadData();
    }

    public function updatedFilterSeverity()
    {
        $this->loadData();
    }

    public function createConflict()
    {
        $this->resetValidation();
        $this->reset([
            'conflict_id',
            'form_employee_involved_id',
            'form_title',
            'form_description',
            'form_date_reported',
            'form_severity',
            'form_status',
            'form_resolution_notes'
        ]);
        $this->form_date_reported = date('Y-m-d');
        $this->form_severity = 'Moyenne';
        $this->form_status = 'En cours';
        $this->isConflictOffcanvasOpen = true;
    }

    public function editConflict($id)
    {
        $this->resetValidation();
        $c = HrConflict::findOrFail($id);

        $this->conflict_id = $c->id;
        $this->form_employee_involved_id = $c->employee_involved_id;
        $this->form_title = $c->title;
        $this->form_description = $c->description;
        $this->form_date_reported = Carbon::parse($c->date_reported)->format('Y-m-d');
        $this->form_severity = $c->severity;
        $this->form_status = $c->status;
        $this->form_resolution_notes = $c->resolution_notes;

        $this->isConflictOffcanvasOpen = true;
    }

    public function saveConflict()
    {
        $this->validate([
            'form_employee_involved_id' => 'nullable|exists:teachers,id',
            'form_title' => 'required|string|max:255',
            'form_description' => 'required|string',
            'form_date_reported' => 'required|date',
            'form_severity' => 'required|in:Faible,Moyenne,Critique',
            'form_status' => 'required|in:En cours,Résolu,En attente,Escalé',
            'form_resolution_notes' => 'nullable|string',
        ]);

        HrConflict::updateOrCreate(
            ['id' => $this->conflict_id],
            [
                'employee_involved_id' => $this->form_employee_involved_id ?: null,
                'reporter_id' => Auth::id(),
                'title' => $this->form_title,
                'description' => $this->form_description,
                'date_reported' => $this->form_date_reported,
                'severity' => $this->form_severity,
                'status' => $this->form_status,
                'resolution_notes' => ($this->form_status === 'Résolu') ? $this->form_resolution_notes : $this->form_resolution_notes,
            ]
        );

        $this->isConflictOffcanvasOpen = false;
        $this->loadData();
        session()->flash('success', 'Incident/Conflit enregistré avec succès.');
    }

    public function promptDelete($id)
    {
        $this->deletingId = $id;
        $this->isConfirmDeleteModalOpen = true;
    }

    public function deleteConflict()
    {
        if ($this->deletingId) {
            HrConflict::destroy($this->deletingId);
            $this->deletingId = null;
            $this->isConfirmDeleteModalOpen = false;
            $this->loadData();
            session()->flash('success', 'Incident supprimé avec succès.');
        }
    }
}
