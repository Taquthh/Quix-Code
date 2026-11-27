<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, SetLocale::SUPPORTED, true), 404);

        if ($user = $request->user()) {
            $user->forceFill(['locale' => $locale])->saveQuietly();
        }

        $cookie = Cookie::forever('locale', $locale);
        $previous = url()->previous();
        $path = (string) parse_url($previous, PHP_URL_PATH);

        if (preg_match('#^/(id|en)(/.*)?$#', $path, $matches) === 1) {
            $suffix = $matches[2] ?? '';
            $query = parse_url($previous, PHP_URL_QUERY);
            $target = '/'.$locale.$suffix.(filled($query) ? '?'.$query : '');

            return redirect()->to($target)->withCookie($cookie);
        }

        return back()->withCookie($cookie);
    }
}
