<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ProAdmissions extends Page
{
    protected static ?string $title = 'Admissions';
    protected static ?string $navigationLabel = 'Admissions';
    protected static ?string $navigationGroup = 'Scolarité';
    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    // Override layout to use our Pro Max SIGS Layout
    protected static string $layout = 'layouts.sigs-layout';

    protected static string $view = 'filament.pages.pro-admissions';

    public $admissions = [];
    public $searchQuery = '';
    public $statusFilter = 'all';
    public $yearFilter = 'all';

    // Form fields
    public $admission_id;
    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $requested_class;
    public $status = 'En attente';
    public $score = 0;
    
    // New fields
    public $date_of_birth;
    public $gender;
    public $address;
    public $previous_school;
    public $parent_name;
    public $parent_phone;
    public $parent_email;
    public $medical_notes;

    // UI state
    public $isOffcanvasOpen = false;
    public $editMode = false;
    public $viewMode = false;
    public $activeCandidate = null;

    protected $rules = [
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required|email|max:255',
        'requested_class' => 'required|string|max:255',
        'status' => 'required|string',
        'score' => 'nullable|numeric|min:0|max:100',
    ];

    public function mount()
    {
        $this->loadAdmissions();
    }

    public function loadAdmissions()
    {
        $query = \App\Models\Admission::query();
        
        if ($this->searchQuery) {
            $query->where(function($q) {
                $q->where('first_name', 'like', '%' . $this->searchQuery . '%')
                  ->orWhere('last_name', 'like', '%' . $this->searchQuery . '%')
                  ->orWhere('email', 'like', '%' . $this->searchQuery . '%');
            });
        }

        if ($this->statusFilter && $this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->yearFilter && $this->yearFilter !== 'all') {
            $query->whereYear('created_at', $this->yearFilter);
        }

        $this->admissions = $query->latest()->get()->map(function($admission) {
            $admission->name = $admission->first_name . ' ' . $admission->last_name;
            $admission->date = $admission->created_at->format('d M Y');
            return $admission;
        })->toArray();
    }

    public function updatedSearchQuery()
    {
        $this->loadAdmissions();
    }

    public function updatedStatusFilter()
    {
        $this->loadAdmissions();
    }

    public function updatedYearFilter()
    {
        $this->loadAdmissions();
    }

    public function exportExcel()
    {
        // Simple mock for export excel (in a real app, use Maatwebsite/Excel)
        session()->flash('success', 'La liste des admissions a été exportée au format Excel avec succès.');
    }

    public function exportPDF()
    {
        // Simple mock for export PDF (in a real app, use barryvdh/laravel-dompdf)
        session()->flash('success', 'La liste des admissions a été exportée au format PDF avec succès.');
    }

    public function createAdmission()
    {
        $this->resetValidation();
        $this->reset([
            'admission_id', 'first_name', 'last_name', 'email', 'phone', 'requested_class', 'score',
            'date_of_birth', 'gender', 'address', 'previous_school', 'parent_name', 'parent_phone', 'parent_email', 'medical_notes'
        ]);
        $this->status = 'En attente';
        $this->editMode = false;
        $this->viewMode = false;
        $this->isOffcanvasOpen = true;
    }

    public function viewAdmission($id)
    {
        $admission = \App\Models\Admission::findOrFail($id);
        $this->activeCandidate = $admission->toArray();
        $this->activeCandidate['name'] = $admission->first_name . ' ' . $admission->last_name;
        $this->activeCandidate['date'] = $admission->created_at->format('d M Y');
        
        $this->viewMode = true;
        $this->editMode = false;
        $this->isOffcanvasOpen = true;
    }

    public function goToProfile($id)
    {
        return redirect()->to('/admissions/' . $id);
    }

    public function editAdmission($id)
    {
        $this->resetValidation();
        $admission = \App\Models\Admission::findOrFail($id);
        $this->admission_id = $admission->id;
        $this->first_name = $admission->first_name;
        $this->last_name = $admission->last_name;
        $this->email = $admission->email;
        $this->phone = $admission->phone;
        $this->requested_class = $admission->requested_class;
        $this->status = $admission->status;
        $this->score = $admission->score;
        
        $this->date_of_birth = $admission->date_of_birth;
        $this->gender = $admission->gender;
        $this->address = $admission->address;
        $this->previous_school = $admission->previous_school;
        $this->parent_name = $admission->parent_name;
        $this->parent_phone = $admission->parent_phone;
        $this->parent_email = $admission->parent_email;
        $this->medical_notes = $admission->medical_notes;
        
        $this->editMode = true;
        $this->viewMode = false;
        $this->isOffcanvasOpen = true;
    }

    public function saveAdmission()
    {
        $this->validate();

        $colors = ['e879f9', '3b82f6', '10b981', 'f59e0b', 'f43f5e', '8b5cf6', 'ec4899'];
        $color = $colors[array_rand($colors)];
        $initials = strtoupper(substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1));

        $data = [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'requested_class' => $this->requested_class,
            'status' => $this->status,
            'score' => $this->score ?: 0,
            'initials' => $initials,
            'date_of_birth' => $this->date_of_birth,
            'gender' => $this->gender,
            'address' => $this->address,
            'previous_school' => $this->previous_school,
            'parent_name' => $this->parent_name,
            'parent_phone' => $this->parent_phone,
            'parent_email' => $this->parent_email,
            'medical_notes' => $this->medical_notes,
        ];

        if ($this->editMode && $this->admission_id) {
            $admission = \App\Models\Admission::find($this->admission_id);
            if ($admission) {
                $data['color'] = $admission->color ?? $color;
                $admission->update($data);
            }
        } else {
            $data['color'] = $color;
            \App\Models\Admission::create($data);
        }

        $this->isOffcanvasOpen = false;
        $this->loadAdmissions();
    }

    public function deleteAdmission($id)
    {
        \App\Models\Admission::findOrFail($id)->delete();
        $this->loadAdmissions();
    }

    public function setStatus($id, $status)
    {
        $admission = \App\Models\Admission::findOrFail($id);
        $admission->status = $status;
        $admission->save();
        $this->loadAdmissions();
    }
}
