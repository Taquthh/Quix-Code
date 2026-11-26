<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** T4-06: tiket revisi terkait tahapan (dasar kuota revisi gratis) dan tangkapan layar di disk privat. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('revision_tickets', function (Blueprint $table) {
            $table->foreignId('milestone_id')->nullable()->constrained('project_milestones')->nullOnDelete();
            $table->string('screenshot_path')->nullable();

            $table->index(['project_id', 'milestone_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::table('revision_tickets', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'milestone_id', 'category']);
            $table->dropConstrainedForeignId('milestone_id');
            $table->dropColumn('screenshot_path');
        });
    }
};
