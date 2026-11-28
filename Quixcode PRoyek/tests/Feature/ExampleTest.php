<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('root mengarahkan ke bahasa yang sesuai', function () {
    // Tanpa preferensi apa pun: harus ke salah satu bahasa yang didukung.
    expect($this->get('/')->headers->get('Location'))->toBeIn([url('/id'), url('/en')]);

    // Header Accept-Language dihormati.
    $this->withHeader('Accept-Language', 'en-US,en;q=0.9')->get('/')->assertRedirect('/en');
    $this->withHeader('Accept-Language', 'id-ID,id;q=0.9,en;q=0.5')->get('/')->assertRedirect('/id');
});

test('root mendahulukan preferensi bahasa akun daripada Accept-Language', function () {
    $user = User::factory()->client()->create(['locale' => 'id']);

    $this->actingAs($user)
        ->withHeader('Accept-Language', 'en')
        ->get('/')
        ->assertRedirect('/id');
});
