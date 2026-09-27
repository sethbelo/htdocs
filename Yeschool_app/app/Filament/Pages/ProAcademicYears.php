<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\AcademicYear;
use App\Models\Enrollment;

class ProAcademicYears extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static string $layout = 'layouts.sigs-layout';
    protected static string $view = 'filament.pages.pro-academic-years';
    protected static ?string $title = 'Années Scolaires';

    public $academicYears = [];

    // Filters
    public $searchQuery = '';
    public $statusFilter = 'all';

    // Report
    public $isReportModalOpen = false;
    public $chartData = [];

    // Offcanvas / Form
    public $isOffcanvasOpen = false;
    public $year_id;
    public $form_name;
    public $form_start_date;
    public $form_end_date;
    public $form_is_active = false;

    public function mount()
    {
        $this->loadYears();
    }

    public function loadYears()
    {
        $query = AcademicYear::orderByDesc('start_date');

        if ($this->searchQuery) {
            $query->where('name', 'like', '%' . $this->searchQuery . '%');
        }

        if ($this->statusFilter !== 'all') {
            $isActive = $this->statusFilter === 'active';
            $query->where('is_active', $isActive);
        }

        $this->academicYears = $query->get()->toArray();
    }

    public function updatedSearchQuery()
    {
        $this->loadYears();
    }

    public function updatedStatusFilter()
    {
        $this->loadYears();
    }

    public function openReport()
    {
        $yearsData = [];
        $years = AcademicYear::orderBy('start_date')->get();
        foreach ($years as $y) {
            $yearsData[] = [
                'name' => $y->name,
                'students_count' => Enrollment::where('academic_year_id', $y->id)->count()
            ];
        }
        $this->chartData = $yearsData;
        $this->isReportModalOpen = true;
    }

    public function createYear()
    {
        $this->resetValidation();
        $this->reset(['year_id', 'form_name', 'form_start_date', 'form_end_date', 'form_is_active']);
        $this->isOffcanvasOpen = true;
    }

    public function editYear($id)
    {
        $this->resetValidation();
        $year = AcademicYear::findOrFail($id);
        
        $this->year_id = $year->id;
        $this->form_name = $year->name;
        $this->form_start_date = $year->start_date ? \Carbon\Carbon::parse($year->start_date)->format('Y-m-d') : null;
        $this->form_end_date = $year->end_date ? \Carbon\Carbon::parse($year->end_date)->format('Y-m-d') : null;
        $this->form_is_active = $year->is_active;

        $this->isOffcanvasOpen = true;
    }

    public function saveYear()
    {
        $this->validate([
            'form_name' => 'required|string|max:255',
            'form_start_date' => 'required|date',
            'form_end_date' => 'required|date|after:form_start_date',
            'form_is_active' => 'boolean',
        ]);

        if ($this->form_is_active) {
            AcademicYear::where('id', '!=', $this->year_id)->update(['is_active' => false]);
        }

        AcademicYear::updateOrCreate(
            ['id' => $this->year_id],
            [
                'name' => $this->form_name,
                'start_date' => $this->form_start_date,
                'end_date' => $this->form_end_date,
                'is_active' => $this->form_is_active,
            ]
        );

        $this->isOffcanvasOpen = false;
        $this->loadYears();
        session()->flash('success', 'Année scolaire enregistrée avec succès.');
    }

    public function deleteYear($id)
    {
        AcademicYear::findOrFail($id)->delete();
        $this->loadYears();
    }

    public function setActiveYear($id)
    {
        AcademicYear::query()->update(['is_active' => false]);
        AcademicYear::where('id', $id)->update(['is_active' => true]);
        $this->loadYears();
    }
}
