<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Tambah kolom link (opsional) untuk menyimpan URL terkait tugas
            if (!Schema::hasColumn('tasks', 'link')) {
                $table->string('link')->nullable()->after('description');
            }

            // Tambah kolom color (opsional) untuk menyimpan warna kartu tugas
            if (!Schema::hasColumn('tasks', 'color')) {
                $table->string('color', 50)->nullable()->after('link');
            }
        });
    }

    /**
     * Rollback migrasi.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'link')) {
                $table->dropColumn('link');
            }
            if (Schema::hasColumn('tasks', 'color')) {
                $table->dropColumn('color');
            }
        });
    }
};
