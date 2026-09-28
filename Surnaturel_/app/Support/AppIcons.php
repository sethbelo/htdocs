<?php

namespace App\Support;

/**
 * Icônes « application » du lanceur (style formes géométriques plates, charte Surnaturel) :
 * une icône par rubrique, dessinée sur une grille 48 × 48.
 */
class AppIcons
{
    private const P = '#134A63'; // pétrole

    private const T = '#53BDC5'; // turquoise

    private const Y = '#FFCE34'; // jaune

    private const R = '#DB0D17'; // rouge

    private const C = '#F2705E'; // corail

    private const O = '#F59A3B'; // orange

    private const V = '#8B5A83'; // prune

    /**
     * @return array<string, string>
     */
    public static function shapes(): array
    {
        [$P, $T, $Y, $R, $C, $O, $V] = [self::P, self::T, self::Y, self::R, self::C, self::O, self::V];

        return [
            'launcher' => "<circle cx='14' cy='14' r='9' fill='$T'/><path d='M34 5a9 9 0 0 1 9 9h-9z' fill='$V'/><path d='M25 14a9 9 0 0 1 9-9v9z' fill='$V' opacity='.7'/><circle cx='14' cy='34' r='9' fill='$C'/><rect x='25' y='25' width='18' height='18' rx='5' fill='$Y'/>",
            'home' => "<path d='M6 22 24 7l18 15v19a3 3 0 0 1-3 3H9a3 3 0 0 1-3-3z' fill='$T'/><path d='M6 22 24 7l18 15' fill='none' stroke='$P' stroke-width='5' stroke-linecap='round' stroke-linejoin='round'/><rect x='19' y='29' width='10' height='15' rx='2' fill='$Y'/>",
            'dashboard' => "<rect x='5' y='24' width='9' height='19' rx='3' fill='$V'/><rect x='19' y='14' width='9' height='29' rx='3' fill='$C'/><rect x='33' y='5' width='10' height='38' rx='3' fill='$Y'/><path d='M5 16 18 8l9 5 16-9' fill='none' stroke='$T' stroke-width='3.5' stroke-linecap='round' stroke-linejoin='round'/>",
            'approvals.index' => "<path d='m24 4 5 4 6-1 2 6 5 3-2 6 2 6-5 3-2 6-6-1-5 4-5-4-6 1-2-6-5-3 2-6-2-6 5-3 2-6 6 1z' fill='$T'/><path d='m16 24 6 6 11-12' fill='none' stroke='$P' stroke-width='5' stroke-linecap='round' stroke-linejoin='round'/>",
            'tasks.index' => "<rect x='7' y='5' width='34' height='38' rx='6' fill='$P'/><rect x='13' y='13' width='8' height='8' rx='2' fill='$Y'/><rect x='24' y='15' width='12' height='4' rx='2' fill='$T'/><rect x='13' y='27' width='8' height='8' rx='2' fill='$C'/><rect x='24' y='29' width='12' height='4' rx='2' fill='$T' opacity='.7'/>",
            'direction.payroll' => "<rect x='7' y='4' width='28' height='38' rx='5' fill='$T'/><rect x='12' y='11' width='18' height='4' rx='2' fill='$P'/><rect x='12' y='19' width='14' height='4' rx='2' fill='$P' opacity='.6'/><circle cx='35' cy='34' r='10' fill='$R'/><path d='m30 34 4 4 6-7' fill='none' stroke='#fff' stroke-width='3.5' stroke-linecap='round' stroke-linejoin='round'/>",
            'clients.index' => "<circle cx='31' cy='15' r='8' fill='$V'/><path d='M18 42a13 13 0 0 1 26 0z' fill='$V' opacity='.75'/><circle cx='17' cy='18' r='9' fill='$O'/><path d='M3 44a14 14 0 0 1 28 0z' fill='$C'/>",
            'pipeline' => "<path d='M4 7h40L30 25v14l-12 5V25z' fill='$T'/><path d='M4 7h40l-6 7H10z' fill='$P'/><path d='M18 30h12v9l-12 5z' fill='$Y'/>",
            'quotes.index' => "<rect x='6' y='4' width='30' height='40' rx='5' fill='$Y'/><rect x='11' y='12' width='18' height='4' rx='2' fill='$P'/><rect x='11' y='20' width='14' height='4' rx='2' fill='$P' opacity='.55'/><path d='m27 38 14-14 5 5-14 14h-5z' fill='$C'/>",
            'orders.index' => "<rect x='7' y='7' width='34' height='37' rx='6' fill='$P'/><rect x='16' y='3' width='16' height='9' rx='3' fill='$Y'/><rect x='13' y='19' width='22' height='4' rx='2' fill='$T'/><rect x='13' y='27' width='16' height='4' rx='2' fill='$T' opacity='.7'/><rect x='13' y='35' width='10' height='4' rx='2' fill='$C'/>",
            'loyalty.index' => "<rect x='5' y='20' width='38' height='24' rx='4' fill='$C'/><rect x='3' y='13' width='42' height='9' rx='3' fill='$R'/><rect x='21' y='13' width='6' height='31' fill='$Y'/><path d='M24 13c-4-8-13-8-12-2 1 4 8 2 12 2 4 0 11 2 12-2 1-6-8-6-12 2z' fill='$Y'/>",
            'followups.index' => "<path d='M8 6h8l3 9-5 3a24 24 0 0 0 11 11l3-5 9 3v8a4 4 0 0 1-4 4C17 39 4 26 4 10a4 4 0 0 1 4-4z' fill='$T'/><path d='M28 4h14a3 3 0 0 1 3 3v9a3 3 0 0 1-3 3h-7l-6 5v-5h-1a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3z' fill='$Y'/>",
            'cash.checkout' => "<rect x='3' y='12' width='42' height='26' rx='5' fill='$T'/><rect x='3' y='18' width='42' height='5' fill='$P' opacity='.35'/><circle cx='24' cy='28' r='6' fill='$Y'/><rect x='8' y='7' width='32' height='5' rx='2.5' fill='$P'/>",
            'pos' => "<path d='M6 16h36v24a4 4 0 0 1-4 4H10a4 4 0 0 1-4-4z' fill='$P'/><path d='M8 5h32l5 11H3z' fill='$Y'/><path d='M3 16h9v3a4.5 4.5 0 0 1-9 0zM12 16h8v3a4 4 0 0 1-8 0zM20 16h8v3a4 4 0 0 1-8 0zM28 16h8v3a4 4 0 0 1-8 0zM36 16h9v3a4.5 4.5 0 0 1-9 0z' fill='$C'/><rect x='19' y='30' width='10' height='14' rx='2' fill='$T'/>",
            'cash.sessions' => "<rect x='5' y='22' width='38' height='22' rx='5' fill='$P'/><rect x='10' y='6' width='28' height='13' rx='3' fill='$T'/><rect x='14' y='10' width='14' height='5' rx='2' fill='$P' opacity='.55'/><rect x='10' y='28' width='6' height='5' rx='1.5' fill='$Y'/><rect x='21' y='28' width='6' height='5' rx='1.5' fill='$Y'/><rect x='32' y='28' width='6' height='5' rx='1.5' fill='$C'/>",
            'design.board' => "<path d='M24 4C12 4 4 12 4 22c0 9 7 14 13 12 4-1 3-6 7-6 5 0 5 5 11 5s9-6 9-12C44 11 35 4 24 4z' fill='$T'/><circle cx='14' cy='18' r='4' fill='$R'/><circle cx='24' cy='12' r='4' fill='$Y'/><circle cx='34' cy='18' r='4' fill='$V'/><path d='m30 45 13-19 3 2-11 20z' fill='$P'/>",
            'production.board' => "<rect x='12' y='4' width='24' height='12' rx='2' fill='$T'/><rect x='4' y='14' width='40' height='20' rx='5' fill='$P'/><rect x='12' y='28' width='24' height='16' rx='2' fill='#fff'/><rect x='16' y='33' width='16' height='3' rx='1.5' fill='$C'/><rect x='16' y='38' width='11' height='3' rx='1.5' fill='$Y'/><circle cx='37' cy='21' r='2.5' fill='$Y'/>",
            'machines.index' => "<path d='m24 3 5 1 1 5 4 2 5-2 3 4-2 5 2 4 5 1v6l-5 1-2 4 2 5-3 4-5-2-4 2-1 5h-6l-1-5-4-2-5 2-3-4 2-5-2-4-5-1v-6l5-1 2-4-2-5 3-4 5 2 4-2 1-5z' fill='$V'/><circle cx='24' cy='24' r='8' fill='$Y'/>",
            'deliveries.index' => "<rect x='2' y='11' width='27' height='22' rx='3' fill='$T'/><path d='M29 18h9l7 8v7H29z' fill='$P'/><circle cx='11' cy='36' r='5' fill='$P'/><circle cx='37' cy='36' r='5' fill='$P'/><circle cx='11' cy='36' r='2' fill='$Y'/><circle cx='37' cy='36' r='2' fill='$Y'/>",
            'products.index' => "<path d='m24 4 18 9-18 9L6 13z' fill='$Y'/><path d='m6 13 18 9v22L6 35z' fill='$O'/><path d='m42 13-18 9v22l18-9z' fill='$V'/>",
            'inventory.index' => "<rect x='4' y='24' width='19' height='18' rx='3' fill='$O'/><rect x='25' y='24' width='19' height='18' rx='3' fill='$T'/><rect x='14' y='5' width='20' height='18' rx='3' fill='$Y'/><rect x='20' y='5' width='8' height='6' fill='$P' opacity='.35'/>",
            'inventory.operations' => "<rect x='4' y='4' width='40' height='40' rx='9' fill='$P'/><path d='M17 36V14m0 0-6 6m6-6 6 6' fill='none' stroke='$T' stroke-width='4.5' stroke-linecap='round' stroke-linejoin='round'/><path d='M31 12v22m0 0-6-6m6 6 6-6' fill='none' stroke='$Y' stroke-width='4.5' stroke-linecap='round' stroke-linejoin='round'/>",
            'inventory.transfers' => "<rect x='4' y='8' width='16' height='16' rx='4' fill='$T'/><rect x='28' y='24' width='16' height='16' rx='4' fill='$Y'/><path d='M22 16h16l-5-5m5 5-5 5M26 32H10l5-5m-5 5 5 5' fill='none' stroke='$P' stroke-width='3.5' stroke-linecap='round' stroke-linejoin='round'/>",
            'inventory.report' => "<rect x='4' y='6' width='40' height='36' rx='8' fill='$P'/><path d='M8 26h8l4-10 7 18 5-12 3 4h5' fill='none' stroke='$T' stroke-width='4' stroke-linecap='round' stroke-linejoin='round'/><circle cx='38' cy='13' r='4' fill='$R'/>",
            'inventory.reports' => "<rect x='7' y='4' width='34' height='40' rx='5' fill='$Y'/><rect x='13' y='26' width='5' height='12' rx='1.5' fill='$P'/><rect x='21' y='19' width='5' height='19' rx='1.5' fill='$C'/><rect x='29' y='12' width='5' height='26' rx='1.5' fill='$V'/>",
            'purchases.index' => "<path d='M8 15h32l-3 27a3 3 0 0 1-3 2H14a3 3 0 0 1-3-2z' fill='$C'/><path d='M16 17v-5a8 8 0 0 1 16 0v5' fill='none' stroke='$P' stroke-width='4' stroke-linecap='round'/><rect x='8' y='15' width='32' height='6' fill='$R' opacity='.5'/>",
            'suppliers.index' => "<path d='M4 44V20l10 6v-6l10 6v-6l10 6V8h10v36z' fill='$P'/><rect x='34' y='4' width='10' height='6' rx='2' fill='$T'/><rect x='9' y='33' width='6' height='6' rx='1' fill='$Y'/><rect x='20' y='33' width='6' height='6' rx='1' fill='$Y'/><rect x='31' y='33' width='6' height='6' rx='1' fill='$Y'/>",
            'invoices.index' => "<path d='M8 4h32v40l-5-3-5 3-6-3-6 3-5-3-5 3z' fill='$T'/><circle cx='24' cy='21' r='9' fill='$P'/><path d='M27 17h-4a2 2 0 0 0 0 4h2a2 2 0 0 1 0 4h-4m3-10v2m0 8v2' fill='none' stroke='$Y' stroke-width='2.2' stroke-linecap='round'/>",
            'treasury.index' => "<path d='M4 17 24 5l20 12z' fill='$P'/><rect x='7' y='20' width='6' height='16' rx='1' fill='$T'/><rect x='21' y='20' width='6' height='16' rx='1' fill='$T'/><rect x='35' y='20' width='6' height='16' rx='1' fill='$T'/><rect x='4' y='38' width='40' height='6' rx='2' fill='$Y'/>",
            'accounting.index' => "<rect x='4' y='4' width='40' height='40' rx='10' fill='$Y'/><circle cx='15' cy='15' r='6' fill='$V'/><circle cx='33' cy='33' r='6' fill='$T'/><path d='M36 10 12 38' stroke='$P' stroke-width='5' stroke-linecap='round'/>",
            'expenses.index' => "<path d='M9 4h30v40l-5-4-5 4-5-4-5 4-5-4-5 4z' fill='$C'/><rect x='14' y='12' width='20' height='4' rx='2' fill='#fff'/><rect x='14' y='20' width='14' height='4' rx='2' fill='#fff' opacity='.8'/><circle cx='31' cy='30' r='5' fill='$Y'/>",
            'vouchers.index' => "<path d='M4 12h40v8a4 4 0 0 0 0 8v8H4v-8a4 4 0 0 0 0-8z' fill='$V'/><path d='M17 12v24' stroke='$Y' stroke-width='3' stroke-dasharray='3 3'/><rect x='22' y='19' width='16' height='4' rx='2' fill='$Y'/><rect x='22' y='26' width='11' height='3' rx='1.5' fill='#fff' opacity='.7'/>",
            'hr.employees' => "<rect x='6' y='8' width='36' height='34' rx='7' fill='$T'/><rect x='18' y='3' width='12' height='9' rx='3' fill='$P'/><circle cx='24' cy='22' r='6' fill='$P'/><path d='M13 38a11 11 0 0 1 22 0z' fill='$Y'/>",
            'hr.departments' => "<rect x='18' y='4' width='12' height='10' rx='3' fill='$P'/><rect x='4' y='34' width='12' height='10' rx='3' fill='$T'/><rect x='18' y='34' width='12' height='10' rx='3' fill='$Y'/><rect x='32' y='34' width='12' height='10' rx='3' fill='$C'/><path d='M24 14v20M10 34v-8h28v8' fill='none' stroke='$P' stroke-width='3'/>",
            'hr.attendance' => "<circle cx='24' cy='25' r='19' fill='$P'/><circle cx='24' cy='25' r='14' fill='$T'/><path d='M24 15v10l7 5' fill='none' stroke='$P' stroke-width='4' stroke-linecap='round' stroke-linejoin='round'/><circle cx='37' cy='9' r='5' fill='$R'/>",
            'hr.leaves' => "<circle cx='33' cy='14' r='9' fill='$Y'/><path d='M4 44c6-8 14-11 22-11s14 3 18 11z' fill='$T'/><path d='M15 34 20 10' stroke='$P' stroke-width='3' stroke-linecap='round'/><path d='M20 10c-6-4-13-1-15 4 5-2 10-1 15-4zm0 0c5-5 12-4 15 0-5 0-10-1-15 0z' fill='$C'/>",
            'hr.payroll' => "<rect x='4' y='10' width='40' height='32' rx='7' fill='$V'/><path d='M4 17h33a7 7 0 0 1 7 7v4H31a5 5 0 0 1 0-10' fill='$C'/><circle cx='33' cy='24' r='3' fill='$Y'/><rect x='10' y='5' width='26' height='6' rx='3' fill='$T'/>",
            'hr.cards' => "<rect x='3' y='9' width='42' height='30' rx='6' fill='$P'/><circle cx='15' cy='22' r='5' fill='$Y'/><path d='M8 34a7 7 0 0 1 14 0z' fill='$Y'/><rect x='26' y='18' width='13' height='4' rx='2' fill='$T'/><rect x='26' y='26' width='9' height='4' rx='2' fill='#fff' opacity='.6'/><rect x='3' y='9' width='42' height='5' rx='2' fill='$T' opacity='.5'/>",
            'hr.affiliations' => "<path d='M24 43S4 31 4 16a10 10 0 0 1 20-3 10 10 0 0 1 20 3c0 15-20 27-20 27z' fill='$C'/><path d='M24 18v14m-7-7h14' stroke='#fff' stroke-width='5' stroke-linecap='round'/>",
            'documents.index' => "<path d='M4 12a4 4 0 0 1 4-4h11l4 5h17a4 4 0 0 1 4 4v23a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4z' fill='$O'/><path d='M4 20h40v20a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4z' fill='$Y'/><rect x='14' y='27' width='20' height='4' rx='2' fill='$P' opacity='.5'/>",
            'reports.index' => "<path d='M22 6A18 18 0 1 0 42 26H22z' fill='$T'/><path d='M26 2a20 20 0 0 1 20 20H26z' fill='$Y'/><path d='M22 26h20A18 18 0 0 1 22 44z' fill='$V' opacity='.85'/>",
            'audit.index' => "<path d='M24 3 42 9v13c0 11-8 19-18 23C14 41 6 33 6 22V9z' fill='$P'/><path d='M24 3v42C14 41 6 33 6 22V9z' fill='$T' opacity='.55'/><path d='m16 24 6 6 10-11' fill='none' stroke='$Y' stroke-width='4' stroke-linecap='round' stroke-linejoin='round'/>",
            'settings.company' => "<path d='M24 3 42 13.5v21L24 45 6 34.5v-21z' fill='$O'/><path d='M24 3 42 13.5v21L24 24z' fill='$V'/><circle cx='24' cy='24' r='7' fill='$P'/>",
            'pin' => "<rect x='6' y='16' width='36' height='28' rx='6' fill='$P'/><path d='M15 16v-4a9 9 0 0 1 18 0v4' fill='none' stroke='$T' stroke-width='5'/><circle cx='17' cy='30' r='3' fill='$Y'/><circle cx='24' cy='30' r='3' fill='$Y'/><circle cx='31' cy='30' r='3' fill='$Y'/>",
        ];
    }

    public static function svg(string $key, string $class = 'size-full'): string
    {
        $shape = self::shapes()[$key] ?? self::shapes()['launcher'];

        return "<svg viewBox='0 0 48 48' xmlns='http://www.w3.org/2000/svg' class='app-icon {$class}' aria-hidden='true'>{$shape}</svg>";
    }
}
