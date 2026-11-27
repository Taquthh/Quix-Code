<?php

namespace App\Http\Controllers\Public;

use App\Enums\Currency;
use App\Http\Controllers\Controller;
use App\Support\PublicCurrencyEstimator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Mengganti mata uang estimasi tampilan pada etalase publik (T6-03). */
class PublicCurrencyController extends Controller
{
    public function __invoke(Request $request, string $currency): RedirectResponse
    {
        $resolved = Currency::tryFrom(strtoupper($currency));
        abort_unless($resolved !== null, 404);

        $request->session()->put(PublicCurrencyEstimator::SESSION_KEY, $resolved->value);

        return back();
    }
}
