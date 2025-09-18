<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('leads', 'user_id') && !Schema::hasColumn('leads', 'owner_id')) {
            // Drop FK lama jika ada
            try {
                Schema::table('leads', function (Blueprint $table) {
                    $table->dropForeign(['user_id']);
                });
            } catch (\Throwable $e) {
                // abaikan bila FK tidak ada
            }

            Schema::table('leads', function (Blueprint $table) {
                $table->renameColumn('user_id', 'owner_id');
            });

            // Buat FK baru ke users.id dengan set null
            Schema::table('leads', function (Blueprint $table) {
                $table->foreign('owner_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('leads', 'owner_id') && !Schema::hasColumn('leads', 'user_id')) {
            // Drop FK baru jika ada
            try {
                Schema::table('leads', function (Blueprint $table) {
                    $table->dropForeign(['owner_id']);
                });
            } catch (\Throwable $e) {
                // abaikan bila FK tidak ada
            }

            Schema::table('leads', function (Blueprint $table) {
                $table->renameColumn('owner_id', 'user_id');
            });

            // Opsional: buat kembali FK user_id
            Schema::table('leads', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }
};
