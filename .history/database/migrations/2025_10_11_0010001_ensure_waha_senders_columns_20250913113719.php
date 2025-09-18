<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('waha_senders')) {
            Schema::create('waha_senders', function (Blueprint $table) {
                $table->id();
                $table->string('name')->default('');              // boleh kosong default
                $table->string('description')->nullable();
                $table->string('session')->unique();
                $table->string('number')->default('');
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
            return;
        }

        Schema::table('waha_senders', function (Blueprint $table) {
            if (!Schema::hasColumn('waha_senders', 'name')) {
                $table->string('name')->default('')->after('id');
            }
            if (!Schema::hasColumn('waha_senders', 'description')) {
                $table->string('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('waha_senders', 'session')) {
                $table->string('session')->after('description');
            }
            if (!Schema::hasColumn('waha_senders', 'number')) {
                $table->string('number')->default('')->after('session');
            }
            if (!Schema::hasColumn('waha_senders', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('number');
            }
            if (!Schema::hasColumn('waha_senders', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('waha_senders', 'created_at')) {
                $table->timestamps();
            }

            // Pastikan index unik di session (abaikan jika sudah ada)
            try {
                $table->unique('session');
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }

    public function down(): void
    {
        // Demi keamanan kita tidak drop table.
        Schema::table('waha_senders', function (Blueprint $table) {
            // Jika perlu rollback ringan (opsional):
            // Jangan drop session/timestamps agar tidak rusak integritas.
            if (Schema::hasColumn('waha_senders', 'number')) {
                $table->dropColumn('number');
            }
            if (Schema::hasColumn('waha_senders', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('waha_senders', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('waha_senders', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('waha_senders', 'is_default')) {
                $table->dropColumn('is_default');
            }
        });
    }
};
