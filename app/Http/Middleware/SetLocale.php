<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prioritas bahasa: segmen URL publik (/id atau /en) > preferensi akun (users.locale) > cookie "locale" > default (id).
 */
class SetLocale
{
    public const SUPPORTED = ['id', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $routeLocale = $request->route('locale');
        $locale = is_string($routeLocale) && in_array($routeLocale, self::SUPPORTED, true)
            ? $routeLocale
            : null;

        if ($locale === null) {
            // Middleware dapat berjalan dengan model User yang dimuat sebagian kolomnya.
            $locale = $request->user()?->getRawOriginal('locale');
        }

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = $request->cookie('locale');
        }

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);
        Date::setLocale($locale);

        return $next($request);
    }
}
