<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('waha_senders')) return;

        Schema::table('waha_senders', function (Blueprint $table) {
            // make number & session nullable (agar create tanpa input ini tetap lolos)
            if (Schema::hasColumn('waha_senders', 'number')) {
                $table->string('number')->nullable()->change();
            }
            if (Schema::hasColumn('waha_senders', 'session')) {
                $table->string('session')->nullable()->change();
            }

            // kolom tambahan opsional untuk display_name / session_name
            if (!Schema::hasColumn('waha_senders', 'display_name')) {
                $table->string('display_name')->nullable()->after('description');
            }
            if (!Schema::hasColumn('waha_senders', 'session_name')) {
                $table->string('session_name')->nullable()->after('session');
            }
        });

        // Lepas index unik di "session" bila ada (agar null/duplikat aman sebelum connect)
        try {
            DB::statement('ALTER TABLE `waha_senders` DROP INDEX `waha_senders_session_unique`');
        } catch (\Throwable $e) {
            // abaikan bila tidak ada
        }
    }

    public function down(): void
    {
        // tidak memaksa rollback (aman)
    }
};
