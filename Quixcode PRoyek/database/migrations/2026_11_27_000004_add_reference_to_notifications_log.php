<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** T5-10. Referensi pengiriman (mis. invoice_issued:<id>) agar WhatsApp untuk kejadian yang sama tidak terkirim dua kali. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications_log', function (Blueprint $table) {
            $table->string('reference', 120)->nullable();
            $table->index(['channel', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::table('notifications_log', function (Blueprint $table) {
            $table->dropIndex(['channel', 'reference']);
            $table->dropColumn('reference');
        });
    }
};
