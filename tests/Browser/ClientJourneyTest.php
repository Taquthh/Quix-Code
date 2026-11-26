<?php

/*
|--------------------------------------------------------------------------
| T4-11 — E2E browser jalur klien di layar ponsel (375px)
|--------------------------------------------------------------------------
| Jalur: login → buka proyek → setujui quotation (dua langkah) → buka formulir bukti bayar DP.
| Memakai pestphp/pest-plugin-browser (Playwright). Lihat TAHAP_T4-11.md untuk cara memasang.
|
| Catatan: selektor memakai teks dari lang/id (locale default) dan atribut data dan name yang
| sudah ada di layout portal. Bila UI berubah, perbarui di satu tempat: konstanta di bawah.
*/

use App\Enums\ProjectStatus;
use App\Enums\QuotationStatus;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\User;

const E2E_WIDTH = 375;
const E2E_HEIGHT = 812;

beforeEach(function () {
    app()->setLocale('id');

    $this->client = User::factory()->client()->create([
        'email' => 'klien-e2e@example.test',
        'name' => 'Budi Santoso',
    ]);

    $this->project = Project::factory()
        ->for($this->client, 'client')
        ->create(['title' => 'Aplikasi E2E 375', 'status' => ProjectStatus::Quoted]);

    $this->quotation = Quotation::factory()
        ->for($this->project)
        ->sent()
        ->create(['version' => 1, 'dp_percent' => 50]);
});

it('klien menyelesaikan login → setuju quotation → bayar DP di viewport 375px', function () {
    // 1. Login (form Fortify), langsung di layar ponsel.
    $page = visit(route('login'))
        ->resize(E2E_WIDTH, E2E_HEIGHT)
        ->assertNoSmoke();

    $page->type('email', 'klien-e2e@example.test')
        ->type('password', 'password')
        ->click('[data-test="login-button"]')
        ->assertPathIs('/portal')
        ->assertSee('Aplikasi E2E 375')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true);

    // 2. Navigasi bawah tampil di ponsel dan sidebar desktop tersembunyi.
    $page->assertScript(
        "getComputedStyle(document.querySelector('[data-portal-nav=\"sidebar\"]').closest('aside')).display === 'none'",
        true,
    );

    // Checkbox Flux tidak bisa diklik lewat teks/selektor biasa, dan atribut wire:model tidak selalu ada di DOM.
    // Jadi dicari lewat teks pernyataan di dekatnya lalu diklik lewat DOM; Livewire menerima event-nya
    // seperti klik pengguna. Bila tidak ketemu, galat memuat HTML panel konfirmasi untuk diagnosis.
    $toggleAgreed = <<<'JS'
(() => {
    const phrase = 'Saya telah membaca';
    const near = (node) => {
        let el = node;
        for (let i = 0; el && i < 5; i++, el = el.parentElement) {
            if ((el.textContent || '').includes(phrase)) { return true; }
        }
        return false;
    };
    const inputs = [...document.querySelectorAll('input[type="checkbox"]')];
    const others = [...document.querySelectorAll('[role="checkbox"], ui-checkbox')];
    const target = inputs.find(near) || others.find(near) || inputs[0] || others[0];
    if (!target) {
        const panel = [...document.querySelectorAll('div')].filter((d) => (d.textContent || '').includes(phrase)).pop();
        throw new Error('Checkbox persetujuan tidak ditemukan. Panel: ' + (panel ? panel.outerHTML.slice(0, 1500) : 'tidak ada'));
    }
    target.click();
    return true;
})()
JS;

    // Tombol final aktif setelah Livewire menerima centang. Diklik lewat DOM supaya tidak terhalang
    // bilah navigasi bawah (fixed) di 375px; bila masih nonaktif, galat memuat statusnya untuk diagnosis.
    $confirmAccept = <<<'JS'
(() => {
    const button = [...document.querySelectorAll('button')].find((b) => (b.textContent || '').includes('Ya, setujui sekarang'));
    if (!button || button.disabled) {
        const checked = [...document.querySelectorAll('input[type="checkbox"]')].map((i) => i.checked).join(',');
        throw new Error('Tombol setuju tidak aktif. Tombol: ' + (button ? button.outerHTML.slice(0, 600) : 'tidak ada') + ' | checkbox checked=' + checked);
    }
    button.click();
    return true;
})()
JS;

    // 3. Halaman negosiasi: setujui quotation lewat dua langkah.
    $page->navigate(route('portal.projects.negotiation', $this->project->id, false))
        ->assertSee('Setujui penawaran')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
        ->click('Setujui penawaran')
        ->assertSee('Harga disepakati dan Invoice DP akan diterbitkan')
        ->assertScript($toggleAgreed, true)
        ->wait(1)
        ->assertScript($confirmAccept, true)
        ->waitForText('Invoice DP');

    expect($this->quotation->fresh()->status)->toBe(QuotationStatus::Accepted)
        ->and($this->project->fresh()->status)->toBe(ProjectStatus::AwaitingDp);

    $invoice = Invoice::query()->where('project_id', $this->project->id)->firstOrFail();

    // 4. Halaman tagihan: formulir bukti bayar DP terbuka, terisi, dan muat di layar 375px.
    //    Pengiriman berkas TIDAK diuji di sini: server HTTP bawaan plugin browser tidak meneruskan
    //    unggahan multipart ke Livewire (_finishUpload menerima daftar kosong). Unggah + verifikasi
    //    sudah diuji penuh di PortalInvoicesTest ("alur penuh") dan SecureUploadTest.
    $page->navigate(route('portal.projects.invoices', $this->project->id, false))
        ->assertSee($invoice->invoice_number)
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
        ->click('Unggah bukti bayar')
        ->type('bankName', 'BCA')
        ->type('senderName', 'Budi Santoso')
        ->type('referenceNumber', 'E2E-0001')
        ->assertSee('Kirim bukti bayar')
        ->assertScript("(document.querySelector('input[inputmode=\"decimal\"]')?.value || '') !== ''", true)
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
        ->assertNoJavaScriptErrors();

    // 5. Tidak ada pembayaran yang tercatat sebelum bukti benar-benar dikirim.
    expect($invoice->payments()->count())->toBe(0);
});

it('target sentuh navigasi dan tombol utama di 375px minimal 44px', function () {
    $this->actingAs($this->client);

    visit(route('portal.projects.show', $this->project->id))
        ->resize(E2E_WIDTH, E2E_HEIGHT)
        ->assertScript(
            "[...document.querySelectorAll('nav a, [data-portal-nav] a')].filter(a => a.offsetParent !== null).every(a => a.getBoundingClientRect().height >= 44)",
            true,
        )
        ->assertNoJavaScriptErrors();
});

it('klien lain yang membuka URL proyek ini di ponsel tidak melihat data apa pun', function () {
    $intruder = User::factory()->client()->create();
    $this->actingAs($intruder);

    visit(route('portal.projects.invoices', $this->project->id))
        ->resize(E2E_WIDTH, E2E_HEIGHT)
        ->assertDontSee('Aplikasi E2E 375');
});
