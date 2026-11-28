<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T5-11 (aturan scheduler). Pencatat idempotensi: satu pengingat per invoice dan jenisnya, satu laporan SLA per tiket,
 * dan penanda payload webhook yang sudah dibersihkan (baris delivery tetap ada untuk idempotensi dan audit).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20); // due_soon | due_today | overdue
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['invoice_id', 'kind']);
        });

        Schema::table('revision_tickets', function (Blueprint $table) {
            $table->timestamp('sla_breached_at')->nullable();
        });

        Schema::table('webhook_deliveries', function (Blueprint $table) {
            $table->timestamp('payload_purged_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('webhook_deliveries', fn (Blueprint $table) => $table->dropColumn('payload_purged_at'));
        Schema::table('revision_tickets', fn (Blueprint $table) => $table->dropColumn('sla_breached_at'));
        Schema::dropIfExists('invoice_reminders');
    }
};
