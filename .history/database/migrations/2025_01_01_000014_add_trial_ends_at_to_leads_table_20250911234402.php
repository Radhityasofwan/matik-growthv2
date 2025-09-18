<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable()->after('score');
            }
            if (!Schema::hasColumn('leads', 'store_name')) {
                $table->string('store_name')->nullable()->after('phone');
            }
            if (Schema::hasColumn('leads', 'status')) {
                // Pastikan ada index status (skip jika sudah ada)
                try {
                    $table->index('status');
                } catch (\Throwable $e) {
                    // abaikan bila index sudah ada
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'trial_ends_at')) {
                $table->dropColumn('trial_ends_at');
            }
            if (Schema::hasColumn('leads', 'store_name')) {
                $table->dropColumn('store_name');
            }
        });
    }
};
