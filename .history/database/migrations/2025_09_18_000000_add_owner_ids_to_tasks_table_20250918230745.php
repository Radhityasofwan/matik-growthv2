<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'owner_ids')) {
                // JSON lebih enak; fallback ke text jika MySQL lama
                if (method_exists($table, 'json')) {
                    $table->json('owner_ids')->nullable()->after('assignee_id');
                } else {
                    $table->text('owner_ids')->nullable()->after('assignee_id');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'owner_ids')) {
                $table->dropColumn('owner_ids');
            }
        });
    }
};
