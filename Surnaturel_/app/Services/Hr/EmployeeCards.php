<?php

namespace App\Services\Hr;

use App\Models\Employee;
use App\Models\Setting;
use App\Models\User;
use App\Services\Printing\PdfRenderer;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

/**
 * Génération des badges, cartes de service (recto / verso) et cartes de visite des agents.
 */
class EmployeeCards
{
    public const KINDS = [
        'service' => 'Carte de service (recto / verso)',
        'badge' => 'Badge d’accès',
        'business' => 'Carte de visite',
    ];

    public function __construct(private PdfRenderer $renderer) {}

    /**
     * @param  Collection<int, Employee>  $employees
     */
    public function render(Collection $employees, string $kind, string $layout = 'single', bool $withBack = true, bool $issue = true): DomPdf
    {
        $validity = (int) Setting::get('hr.card_validity_years', 2);

        if ($issue && $kind !== 'business') {
            $employees->each(function (Employee $employee) use ($validity): void {
                if (! $employee->card_expires_at || $employee->card_expires_at->isPast()) {
                    $employee->forceFill(['card_issued_at' => today(), 'card_expires_at' => today()->addYears($validity)])->save();
                }
            });
        }

        $employees = EloquentCollection::make($employees->all())->loadMissing('department', 'site');
        [$w, $h] = match ($kind) {
            'badge' => [153.07, 242.65], 'business' => [240.94, 155.91], default => [242.65, 153.07]
        };

        return $this->renderer->make('pdf.cards', [
            'employees' => $employees->values(),
            'kind' => $kind,
            'layout' => $layout,
            'withBack' => $withBack,
            'validity' => $validity,
            'dgName' => User::role('directeur-general')->where('is_active', true)->value('name'),
            'verifyUrls' => $employees->mapWithKeys(fn (Employee $e) => [$e->id => $this->verificationUrl($e)])->all(),
            'paper' => $layout === 'sheet' ? 'a4' : [0, 0, $w, $h],
        ], 'custom');
    }

    public function verificationUrl(Employee $employee): string
    {
        return URL::signedRoute('employees.verify', $employee);
    }
}
