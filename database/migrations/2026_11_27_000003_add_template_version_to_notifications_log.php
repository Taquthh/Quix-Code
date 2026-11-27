<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T5-13 (editor template notifikasi). Versi template yang dipakai tiap pengiriman dicatat di log kirim;
 * null berarti memakai teks bawaan lang/ (belum ada template tersimpan atau notifikasi tanpa template).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications_log', function (Blueprint $table) {
            $table->unsignedInteger('template_version')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('notifications_log', fn (Blueprint $table) => $table->dropColumn('template_version'));
    }
};
