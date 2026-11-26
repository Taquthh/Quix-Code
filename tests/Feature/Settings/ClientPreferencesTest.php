<?php

use App\Enums\Currency;
use App\Livewire\Settings\Profile;
use App\Models\ExchangeRate;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use App\Models\UserConsent;
use App\Support\DisplayCurrency;
use App\Support\Money;
use App\Support\WhatsappConsent;
use Illuminate\Support\Arr;
use Livewire\Livewire;

beforeEach(function () {
    app()->setLocale('id');
    $this->client = User::factory()->client()->create(['name' => 'Budi', 'email' => 'budi@example.com']);
});

function saveProfile(User $user, array $fields = [])
{
    test()->actingAs($user);

    $component = Livewire::test(Profile::class)
        ->set('name', $fields['name'] ?? $user->name)
        ->set('email', $fields['email'] ?? $user->email);

    foreach (Arr::except($fields, ['name', 'email']) as $key => $value) {
        $component->set($key, $value);
    }

    return $component->call('updateProfileInformation');
}

it('klien melihat pengaturan WhatsApp dan mata uang, admin tidak', function () {
    $this->actingAs($this->client)->get('/settings/profile')
        ->assertOk()
        ->assertSee(__('settings.client.whatsapp'))
        ->assertSee(__('settings.client.currency'));

    $this->actingAs(User::factory()->admin()->create())->get('/settings/profile')
        ->assertOk()
        ->assertDontSee(__('settings.client.whatsapp'));
});

it('opt-in WhatsApp tercatat dengan versi, IP, dan waktu', function () {
    config(['quix.whatsapp_consent_version' => '2026-11']);

    saveProfile($this->client, ['whatsapp' => '+62 812-3456-789', 'whatsappOptIn' => true])->assertHasNoErrors();

    $this->client->refresh();
    $consent = UserConsent::query()->sole();

    expect($this->client->whatsapp)->toBe('+628123456789')
        ->and($this->client->whatsapp_opt_in)->toBeTrue()
        ->and($consent->user_id)->toBe($this->client->id)
        ->and($consent->type)->toBe('whatsapp_notifications')
        ->and($consent->version)->toBe('2026-11')
        ->and($consent->ip)->not->toBeNull()
        ->and($consent->granted_at)->not->toBeNull()
        ->and($consent->revoked_at)->toBeNull()
        ->and($this->client->canReceiveWhatsapp())->toBeTrue();
});

it('menyimpan ulang tanpa perubahan tidak menggandakan baris persetujuan', function () {
    saveProfile($this->client, ['whatsapp' => '08123456789', 'whatsappOptIn' => true]);
    saveProfile($this->client->refresh(), ['whatsapp' => '08123456789', 'whatsappOptIn' => true]);

    expect(UserConsent::query()->count())->toBe(1);
});

it('opt-in tanpa nomor ditolak dan tidak ada yang tersimpan', function () {
    saveProfile($this->client, ['name' => 'Nama Baru', 'whatsapp' => '', 'whatsappOptIn' => true])
        ->assertHasErrors(['whatsapp']);

    expect(UserConsent::query()->count())->toBe(0)
        ->and($this->client->refresh()->name)->toBe('Budi')
        ->and($this->client->whatsapp_opt_in)->toBeFalse();
});

it('nomor WhatsApp yang tidak valid ditolak', function (string $number) {
    saveProfile($this->client, ['whatsapp' => $number, 'whatsappOptIn' => true])->assertHasErrors(['whatsapp']);

    expect(UserConsent::query()->count())->toBe(0);
})->with(['abc', '123', '+62-abc-123', '0812345678901234567']);

it('pencabutan menutup persetujuan, mempertahankan riwayat, dan menghentikan WhatsApp', function () {
    saveProfile($this->client, ['whatsapp' => '08123456789', 'whatsappOptIn' => true]);
    saveProfile($this->client->refresh(), ['whatsapp' => '08123456789', 'whatsappOptIn' => false])->assertHasNoErrors();

    $this->client->refresh();
    $consent = UserConsent::query()->sole();

    expect($this->client->whatsapp_opt_in)->toBeFalse()
        ->and($consent->revoked_at)->not->toBeNull()
        ->and($this->client->canReceiveWhatsapp())->toBeFalse();
});

it('opt-in ulang setelah pencabutan membuat baris baru, begitu juga bila versi teks berubah', function () {
    $service = new WhatsappConsent;
    $this->client->forceFill(['whatsapp' => '08123456789'])->save();

    $service->grant($this->client, '203.0.113.5');
    $service->revoke($this->client);
    $service->grant($this->client, '203.0.113.6');

    config(['quix.whatsapp_consent_version' => '2']);
    $service->grant($this->client, '203.0.113.7');

    $rows = UserConsent::query()->orderBy('id')->get();

    expect($rows)->toHaveCount(3)
        ->and($rows[0]->revoked_at)->not->toBeNull()
        ->and($rows[1]->revoked_at)->not->toBeNull()
        ->and($rows[2]->revoked_at)->toBeNull()
        ->and($rows[2]->version)->toBe('2')
        ->and($service->active($this->client)?->is($rows[2]))->toBeTrue();
});

it('tanpa persetujuan aktif tidak ada WhatsApp, walau nomor dan flag terisi', function () {
    $this->client->forceFill(['whatsapp' => '08123456789'])->save();
    expect($this->client->canReceiveWhatsapp())->toBeFalse();

    // Flag saja tanpa baris persetujuan tidak cukup.
    $this->client->forceFill(['whatsapp_opt_in' => true])->save();
    expect($this->client->refresh()->canReceiveWhatsapp())->toBeFalse();

    (new WhatsappConsent)->grant($this->client, '203.0.113.5');
    expect($this->client->refresh()->canReceiveWhatsapp())->toBeTrue();

    $this->client->forceFill(['is_active' => false])->save();
    expect($this->client->refresh()->canReceiveWhatsapp())->toBeFalse();
});

it('staf dan admin tidak pernah menerima WhatsApp', function () {
    $staff = User::factory()->staff()->create(['whatsapp' => '08123456789']);
    (new WhatsappConsent)->grant($staff, '203.0.113.5');

    expect($staff->refresh()->canReceiveWhatsapp())->toBeFalse();
});

it('klien menyimpan mata uang tampilan, dan nilai di luar daftar ditolak', function () {
    saveProfile($this->client, ['preferredCurrency' => 'USD'])->assertHasNoErrors();
    expect($this->client->refresh()->preferred_currency)->toBe(Currency::USD);

    saveProfile($this->client, ['preferredCurrency' => 'EUR'])->assertHasErrors(['preferredCurrency']);
    expect($this->client->refresh()->preferred_currency)->toBe(Currency::USD);
});

it('admin yang mengubah profil tidak menyentuh preferensi klien', function () {
    $admin = User::factory()->admin()->create();

    saveProfile($admin, ['name' => 'Admin Baru', 'whatsappOptIn' => true, 'preferredCurrency' => 'USD'])->assertHasNoErrors();

    expect($admin->refresh()->name)->toBe('Admin Baru')
        ->and($admin->whatsapp_opt_in)->toBeFalse()
        ->and($admin->preferred_currency)->toBe(Currency::IDR)
        ->and(UserConsent::query()->count())->toBe(0);
});

it('mengonversi estimasi dengan bilangan bulat dan pembulatan half up', function () {
    expect(DisplayCurrency::convert(Money::of(1_000_000, Currency::IDR), Currency::USD, '0.00006100')?->amount)->toBe(6100)
        ->and(DisplayCurrency::convert(Money::of(10_000, Currency::USD), Currency::IDR, '16000.50000000')?->amount)->toBe(1_600_050)
        ->and(DisplayCurrency::convert(Money::of(1, Currency::IDR), Currency::USD, '0.00006100')?->amount)->toBe(0)
        ->and(DisplayCurrency::convert(Money::of(1_000_000, Currency::IDR), Currency::USD, 'abc'))->toBeNull()
        ->and(DisplayCurrency::convert(Money::of(1_000_000, Currency::IDR), Currency::USD, '0'))->toBeNull()
        ->and(DisplayCurrency::convert(Money::of(PHP_INT_MAX, Currency::USD), Currency::IDR, '16000'))->toBeNull();
});

it('estimasi hanya muncul bila mata uang pilihan berbeda dan kurs tersedia', function () {
    $money = Money::of(1_000_000, Currency::IDR);

    expect(DisplayCurrency::estimate($money, $this->client))->toBeNull();

    $this->client->forceFill(['preferred_currency' => Currency::USD])->save();
    expect(DisplayCurrency::estimate($money, $this->client))->toBeNull();

    ExchangeRate::factory()->create(['rate' => '0.00006100']);
    expect(DisplayCurrency::estimate($money, $this->client)?->amount)->toBe(6100)
        ->and(DisplayCurrency::estimate($money, null))->toBeNull()
        ->and(DisplayCurrency::estimate(Money::of(500, Currency::USD), $this->client))->toBeNull();
});

it('portal menampilkan perkiraan di samping angka resmi tanpa mengubahnya', function () {
    $project = Project::factory()->for($this->client, 'client')->create();
    Invoice::factory()->for($project)->create(['amount' => 10_000_000, 'paid_amount' => 0]);
    ExchangeRate::factory()->create(['rate' => '0.00006100']);

    $this->actingAs($this->client)->get(route('portal.projects.invoices', $project->id))
        ->assertOk()
        ->assertSee('Rp 10.000.000')
        ->assertDontSee('US$');

    $this->client->forceFill(['preferred_currency' => Currency::USD])->save();

    $this->actingAs($this->client->refresh())->get(route('portal.projects.invoices', $project->id))
        ->assertOk()
        ->assertSee('Rp 10.000.000')
        ->assertSee(__('settings.estimate', ['amount' => 'US$ 610,00']));
});
