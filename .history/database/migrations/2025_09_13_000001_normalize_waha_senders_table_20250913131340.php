<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('waha_senders')) return;

        // Rename session_name -> session (jika perlu)
        if (Schema::hasColumn('waha_senders','session_name') && !Schema::hasColumn('waha_senders','session')) {
            Schema::table('waha_senders', function (Blueprint $t) {
                $t->renameColumn('session_name', 'session');
            });
        }

        Schema::table('waha_senders', function (Blueprint $t) {
            // Pastikan kolom inti ada & default aman
            if (!Schema::hasColumn('waha_senders','name'))        $t->string('name')->default('')->after('id');
            if (!Schema::hasColumn('waha_senders','description')) $t->string('description')->nullable()->after('name');
            if (!Schema::hasColumn('waha_senders','session'))     $t->string('session')->after('description');
            if (!Schema::hasColumn('waha_senders','number'))      $t->string('number')->default('')->after('session');
            if (!Schema::hasColumn('waha_senders','is_active'))   $t->boolean('is_active')->default(true)->after('number');
            if (!Schema::hasColumn('waha_senders','is_default'))  $t->boolean('is_default')->default(false)->after('is_active');
            if (!Schema::hasColumn('waha_senders','created_at'))  $t->timestamps();

            // Kolom non-inti yang kadang ada → buat nullable aman
            if (Schema::hasColumn('waha_senders','display_name')) {
                $t->string('display_name')->nullable()->default(null)->change();
            }

            // Unique di session (abaikan jika sudah ada)
            try { $t->unique('session'); } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        // tidak rollback agresif; biarkan stabil
    }
};
