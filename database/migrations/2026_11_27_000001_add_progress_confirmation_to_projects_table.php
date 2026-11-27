<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T5-05 (pagar 100%): webhook yang meminta [progress:100%] tidak boleh langsung memindahkan status.
 * Kolom ini menandai "menunggu konfirmasi admin"; progres otomatis ditahan di 99% sampai admin menekan konfirmasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->timestamp('progress_pending_confirm_at')->nullable()->after('progress_percent');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('progress_pending_confirm_at');
        });
    }
};
