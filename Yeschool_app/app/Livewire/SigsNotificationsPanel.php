<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Admission;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SigsNotificationsPanel extends Component
{
    public $selectedAdmission = null;
    public $isDetailsModalOpen = false;

    // Listeners to refresh counts globally when other actions happen
    protected $listeners = ['refreshNotifications' => '$refresh'];

    public function validateAdmission($id)
    {
        $admission = Admission::findOrFail($id);
        $admission->status = 'Accepté';
        $admission->is_read = true;
        $admission->save();

        // Also create the student record automatically if needed
        // Let's see: to be friendly, we can check if a student with this email already exists
        $exists = Student::where('email', $admission->email)->exists();
        if (!$exists) {
            Student::create([
                'first_name' => $admission->first_name,
                'last_name' => $admission->last_name,
                'email' => $admission->email,
                'phone' => $admission->phone,
                'parent_name' => $admission->parent_name ?: 'Parent de ' . $admission->first_name,
                'parent_phone' => $admission->parent_phone ?: $admission->phone,
                'parent_email' => $admission->parent_email ?: $admission->email,
                'date_of_birth' => $admission->date_of_birth ?: date('Y-m-d'),
                'gender' => $admission->gender ?: 'M',
                'address' => $admission->address ?: 'Non spécifiée',
                'matricule' => 'MAT-' . strtoupper(substr(md5(time() . $admission->id), 0, 6)),
            ]);
        }

        session()->flash('success_notification', "La demande d'admission de {$admission->first_name} {$admission->last_name} a été validée et l'élève a été inscrit.");
        $this->dispatch('refreshNotifications');
    }

    public function markAsRead($id)
    {
        $admission = Admission::findOrFail($id);
        $admission->is_read = true;
        $admission->save();
        
        $this->dispatch('refreshNotifications');
    }

    public function markAllAsRead()
    {
        Admission::where('is_read', false)->update(['is_read' => true]);
        
        $this->dispatch('refreshNotifications');
        session()->flash('success_notification', "Toutes les alertes d'admissions ont été marquées comme lues.");
    }

    public function viewDetails($id)
    {
        $this->selectedAdmission = Admission::findOrFail($id);
        $this->selectedAdmission->is_read = true;
        $this->selectedAdmission->save();

        $this->isDetailsModalOpen = true;
        $this->dispatch('refreshNotifications');
    }

    public function closeDetailsModal()
    {
        $this->isDetailsModalOpen = false;
        $this->selectedAdmission = null;
    }

    public function render()
    {
        $today = Carbon::today();
        $todayMonthDay = $today->format('m-d');
        
        $driverName = DB::connection()->getDriverName();
        $sqlFormat = $driverName === 'sqlite' 
            ? "strftime('%m-%d', date_of_birth) = ?" 
            : "DATE_FORMAT(date_of_birth, '%m-%d') = ?";
        
        // Today's birthdays - Students
        $todayStudentBirthdays = Student::whereRaw($sqlFormat, [$todayMonthDay])->get();
        
        // Today's birthdays - Staff
        $todayTeacherBirthdays = Teacher::where('status', 'Actif')
            ->whereRaw($sqlFormat, [$todayMonthDay])
            ->get();
            
        // Upcoming birthdays (next 7 days, excluding today)
        $upcomingBirthdays = [];
        for ($i = 1; $i <= 7; $i++) {
            $nextDay = $today->copy()->addDays($i);
            $nextMonthDay = $nextDay->format('m-d');
            
            $st = Student::whereRaw($sqlFormat, [$nextMonthDay])->get();
            foreach ($st as $s) {
                $upcomingBirthdays[] = [
                    'name' => "{$s->first_name} {$s->last_name}",
                    'type' => 'Élève',
                    'date_of_birth' => $s->date_of_birth,
                    'days_left' => $i,
                    'date_formatted' => $nextDay->translatedFormat('d F'),
                    'phone' => $s->phone
                ];
            }
            
            $tc = Teacher::where('status', 'Actif')
                ->whereRaw($sqlFormat, [$nextMonthDay])->get();
            foreach ($tc as $t) {
                $upcomingBirthdays[] = [
                    'name' => "{$t->first_name} {$t->last_name}",
                    'type' => 'Collaborateur RH',
                    'date_of_birth' => $t->date_of_birth,
                    'days_left' => $i,
                    'date_formatted' => $nextDay->translatedFormat('d F'),
                    'phone' => $t->phone
                ];
            }
        }

        // Get unread and read admissions
        $unreadAdmissions = Admission::where('is_read', false)->orderByDesc('created_at')->get();
        $readAdmissions = Admission::where('is_read', true)->orderByDesc('created_at')->take(10)->get();

        return view('livewire.sigs-notifications-panel', [
            'todayStudentBirthdays' => $todayStudentBirthdays,
            'todayTeacherBirthdays' => $todayTeacherBirthdays,
            'upcomingBirthdays' => $upcomingBirthdays,
            'unreadAdmissions' => $unreadAdmissions,
            'readAdmissions' => $readAdmissions,
        ]);
    }
}
