<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureUserCanAccessModule
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (!$user) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        if (!$routeName) {
            return $next($request);
        }

        // 1. Settings / Configuration
        if ($routeName === 'filament.admin.pages.pro-settings') {
            abort_unless($user->hasAnyRole(['Super Admin']), 403, 'Accès non autorisé à la configuration.');
        }

        // 2. HR / Personnel
        if ($routeName === 'filament.admin.pages.pro-h-r') {
            abort_unless($user->hasAnyRole(['Super Admin', 'RH', 'Responsable RH', 'Directeur General', 'Directeur Général']), 403, 'Accès non autorisé aux ressources humaines.');
        }

        // 3. Finances & Comptabilité
        $financeRoutes = [
            'filament.admin.pages.pro-accounting',
            'filament.admin.pages.pro-fee-management',
            'filament.admin.pages.frais-scolaires',
        ];
        if (in_array($routeName, $financeRoutes)) {
            abort_unless($user->hasAnyRole(['Super Admin', 'Comptable', 'Caissier', 'Directeur General', 'Directeur Général']), 403, 'Accès non autorisé aux finances et comptabilité.');
        }

        // 4. Scolarité & Académique
        $academicRoutes = [
            'filament.admin.pages.pro-admissions',
            'filament.admin.pages.inscriptions',
            'filament.admin.pages.pro-enrollments',
            'filament.admin.pages.pro-students',
            'filament.admin.pages.pro-classes',
            'filament.admin.pages.pro-academic-years',
            'filament.admin.pages.pro-timetable',
            'filament.admin.pages.pro-grades',
            'filament.admin.pages.pro-attendances',
            'filament.admin.pages.pro-subjects',
            'filament.admin.pages.pro-admission-profile',
        ];
        if (in_array($routeName, $academicRoutes)) {
            abort_unless($user->hasAnyRole([
                'Super Admin', 'Directeur General', 'Directeur Général', 'Prefet',
                'Enseignant', 'Titulaire', 'Surveillant', 'Secretaire', 'Secrétaire'
            ]), 403, 'Accès non autorisé à la scolarité et pédagogie.');
        }

        if ($user->hasRole('Enseignant')) {
            $restrictedRoutesForEnseignant = [
                'filament.admin.pages.pro-admissions',
                'filament.admin.pages.pro-admission-profile',
                'filament.admin.pages.pro-academic-years',
                'filament.admin.pages.pro-enrollments',
                'filament.admin.pages.inscriptions',
                'filament.admin.pages.pro-subjects',
            ];
            if (in_array($routeName, $restrictedRoutesForEnseignant)) {
                abort(403, 'Accès non autorisé.');
            }
        }

        return $next($request);
    }
}
