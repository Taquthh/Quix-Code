<?php

use App\Models\User;
use Illuminate\Support\Facades\File;

/**
 * T4-08: halaman Pengaturan tampil dalam bahasa akun. Teks berkunci bahasa Inggris (__('Save'))
 * diterjemahkan lewat lang/id.json; teks baru yang lupa diterjemahkan akan menggagalkan tes ini.
 */

/**
 * Semua kunci literal __('...') di file yang membentuk halaman Pengaturan.
 *
 * @return array<int, string>
 */
function settingsTranslationKeys(): array
{
    $paths = [
        resource_path('views/livewire/settings'),
        resource_path('views/components/settings'),
        resource_path('views/components/passkey-registration.blade.php'),
        resource_path('views/partials/settings-heading.blade.php'),
        app_path('Livewire/Settings'),
    ];

    $files = [];

    foreach ($paths as $path) {
        $files = array_merge(
            $files,
            is_dir($path) ? array_map(fn ($f) => $f->getPathname(), File::allFiles($path)) : [$path],
        );
    }

    $keys = [];

    foreach ($files as $file) {
        preg_match_all('/__\(\s*([\'"])(.*?)(?<!\\\\)\1\s*[,)]/s', (string) file_get_contents($file), $matches);

        foreach ($matches[2] as $key) {
            // Kunci bernamespace seperti settings.client.currency dicek oleh tes bahasa lain.
            if (! preg_match('/^[a-z_]+(\.[a-z_]+)+$/', $key)) {
                $keys[] = stripslashes($key);
            }
        }
    }

    return array_values(array_unique($keys));
}

it('semua teks halaman Pengaturan punya terjemahan Indonesia', function () {
    $id = json_decode((string) file_get_contents(lang_path('id.json')), true, flags: JSON_THROW_ON_ERROR);

    $missing = array_values(array_filter(
        settingsTranslationKeys(),
        fn (string $key) => ! array_key_exists($key, $id) || trim((string) $id[$key]) === '',
    ));

    expect($missing)->toBe([], 'Tambahkan ke lang/id.json: '.implode(' | ', $missing));
});

it('terjemahan Indonesia mempertahankan placeholder :nama dari teks aslinya', function () {
    $id = json_decode((string) file_get_contents(lang_path('id.json')), true, flags: JSON_THROW_ON_ERROR);

    foreach ($id as $key => $value) {
        preg_match_all('/:[a-z_]+/', $key, $expected);
        preg_match_all('/:[a-z_]+/', $value, $actual);

        expect($actual[0])->toEqualCanonicalizing($expected[0], "Placeholder berbeda pada: {$key}");
    }
});

it('halaman Pengaturan tampil berbahasa Indonesia untuk akun berbahasa Indonesia', function () {
    $user = User::factory()->client()->create(['locale' => 'id']);

    $this->actingAs($user)->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Pengaturan')
        ->assertSee('Profil')
        ->assertSee('Keamanan')
        ->assertSee('Tampilan')
        ->assertSee('Simpan')
        ->assertSee('Hapus akun')
        ->assertSee('Pengaturan profil - ', false)
        ->assertDontSee('Manage your profile and account settings')
        ->assertDontSee('Delete your account and all of its resources');

    $this->actingAs($user)->get(route('appearance.edit'))
        ->assertOk()
        ->assertSee('Terang')
        ->assertSee('Gelap')
        ->assertSee('Sistem')
        ->assertDontSee('Update the appearance settings for your account');

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertOk()
        ->assertSee('Ubah kata sandi')
        ->assertSee('Kata sandi saat ini')
        ->assertDontSee('Current password');
});

it('halaman Pengaturan tetap berbahasa Inggris untuk akun berbahasa Inggris', function () {
    $user = User::factory()->client()->create(['locale' => 'en']);

    $this->actingAs($user)->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Manage your profile and account settings')
        ->assertSee('Delete account')
        ->assertDontSee('Pengaturan profil');
});
