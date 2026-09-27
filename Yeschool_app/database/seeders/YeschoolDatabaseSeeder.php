<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class YeschoolDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Roles
        $roles = [
            'Super Admin',
            'Proprietaire',
            'Directeur General',
            'Prefet',
            'Secretaire',
            'Comptable',
            'Caissier',
            'Enseignant',
            'Titulaire',
            'Parent',
            'Eleve',
            'RH',
            'Bibliothecaire',
            'Infirmier',
            'Surveillant',
            'Administrateur Campus'
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // 2. Create Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@yetoo.com'],
            [
                'name' => 'Super Admin Yetoo',
                'password' => Hash::make('password'),
                'type' => 'super_admin',
                'is_active' => true,
            ]
        );
        $superAdmin->assignRole('Super Admin');

        // 3. Create Demo School
        $school = School::firstOrCreate(
            ['slug' => 'ecole-demo-yetoo'],
            [
                'name' => 'École Démo Yetoo',
                'email' => 'contact@ecole-demo.com',
                'phone' => '+243 81 000 0000',
                'address' => '123 Avenue de l\'École, Kinshasa',
                'status' => 'active',
            ]
        );

        // 4. Create Campus
        $campus = Campus::firstOrCreate(
            ['school_id' => $school->id, 'name' => 'Campus Principal'],
            [
                'address' => '123 Avenue de l\'École, Kinshasa',
            ]
        );

        // 5. Create Academic Year
        $academicYear = AcademicYear::firstOrCreate(
            ['school_id' => $school->id, 'name' => '2026-2027'],
            [
                'start_date' => '2026-09-01',
                'end_date' => '2027-06-30',
                'is_active' => true,
            ]
        );

        // 6. Create School Director
        $director = User::firstOrCreate(
            ['email' => 'directeur@ecole-demo.com'],
            [
                'name' => 'Directeur Démo',
                'password' => Hash::make('password'),
                'school_id' => $school->id,
                'type' => 'admin',
                'is_active' => true,
            ]
        );
        $director->assignRole('Directeur General');

        // 7. Create 3 users for each requested role
        $rolesToCreate = ['Enseignant', 'Comptable', 'RH', 'Caissier', 'Secretaire'];
        $types = [
            'Enseignant' => 'teacher',
            'Comptable' => 'staff',
            'RH' => 'staff',
            'Caissier' => 'staff',
            'Secretaire' => 'staff',
        ];

        foreach ($rolesToCreate as $roleName) {
            for ($i = 1; $i <= 3; $i++) {
                // Normalize accent for email prefix if any
                $emailPrefix = str_replace('é', 'e', strtolower($roleName));
                $email = $emailPrefix . $i . '@ecole-demo.com';
                $name = $roleName . ' ' . $i;

                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $name,
                        'password' => Hash::make('password'),
                        'school_id' => $school->id,
                        'type' => $types[$roleName],
                        'is_active' => true,
                    ]
                );
                $user->assignRole($roleName);
            }
        }
    }
}
