<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Support\DisplayCurrency;

final class CurrencyPreferenceController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        // Compatibilité avec les anciens favoris : la devise d'affichage ne
        // peut plus diverger de la devise comptable choisie à l'installation.
        $request->session()->put([
            'ui.currency' => DisplayCurrency::baseCode(),
            'pos.currency' => DisplayCurrency::baseCode(),
        ]);

        return back(303);
    }
}
