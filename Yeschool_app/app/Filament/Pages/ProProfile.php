<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class ProProfile extends Page
{
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static string $layout = 'layouts.sigs-layout';
    protected static string $view = 'filament.pages.pro-profile';
    protected static ?string $title = 'Mon Profil';

    // Form fields
    public $name;
    public $email;
    public $phone;
    public $address;
    public $password;
    public $password_confirmation;

    // Current document paths
    public $avatar;
    public $cv_path;
    public $dossier_academique_path;

    // Temporary upload objects
    public $avatar_upload;
    public $cv_upload;
    public $dossier_upload;

    public function mount()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->address = $user->address;
        $this->avatar = $user->avatar;
        $this->cv_path = $user->cv_path;
        $this->dossier_academique_path = $user->dossier_academique_path;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function saveProfile()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'avatar_upload' => 'nullable|image|max:2048', // 2MB max
            'cv_upload' => 'nullable|file|mimes:pdf|max:10240', // 10MB max PDF
            'dossier_upload' => 'nullable|file|mimes:pdf|max:10240', // 10MB max PDF
        ];

        if ($this->password) {
            $rules['password'] = 'required|string|min:8|confirmed';
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
        ];

        // Process Avatar Upload
        if ($this->avatar_upload) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $this->avatar_upload->store('avatars', 'public');
            $this->avatar = $data['avatar'];
            $this->avatar_upload = null;
        }

        // Process CV Upload
        if ($this->cv_upload) {
            if ($user->cv_path) {
                Storage::disk('public')->delete($user->cv_path);
            }
            $data['cv_path'] = $this->cv_upload->store('cvs', 'public');
            $this->cv_path = $data['cv_path'];
            $this->cv_upload = null;
        }

        // Process Academic Dossier Upload
        if ($this->dossier_upload) {
            if ($user->dossier_academique_path) {
                Storage::disk('public')->delete($user->dossier_academique_path);
            }
            $data['dossier_academique_path'] = $this->dossier_upload->store('dossiers', 'public');
            $this->dossier_academique_path = $data['dossier_academique_path'];
            $this->dossier_upload = null;
        }

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
            $this->password = null;
            $this->password_confirmation = null;
        }

        $user->update($data);

        session()->flash('success', 'Votre profil a été mis à jour avec succès.');
    }

    public function deleteDocument($type)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($type === 'cv' && $user->cv_path) {
            Storage::disk('public')->delete($user->cv_path);
            $user->update(['cv_path' => null]);
            $this->cv_path = null;
            session()->flash('success', 'CV supprimé avec succès.');
        } elseif ($type === 'dossier' && $user->dossier_academique_path) {
            Storage::disk('public')->delete($user->dossier_academique_path);
            $user->update(['dossier_academique_path' => null]);
            $this->dossier_academique_path = null;
            session()->flash('success', 'Dossier académique supprimé avec succès.');
        }
    }
}
