<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // FIX: Tambahkan pengecekan sebelum membuat kolom
            if (!Schema::hasColumn('tasks', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false)->after('creator_id');
            }
            if (!Schema::hasColumn('tasks', 'start_at')) {
                $table->timestamp('start_at')->nullable()->after('due_date');
            }
            if (!Schema::hasColumn('tasks', 'progress')) {
                $table->tinyInteger('progress')->unsigned()->default(0)->after('is_pinned');
            }
            if (!Schema::hasColumn('tasks', 'icon')) {
                $table->string('icon')->nullable()->after('progress');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // FIX: Buat proses rollback lebih aman
            $columnsToDrop = ['is_pinned', 'start_at', 'progress', 'icon'];
            $existingColumns = array_filter($columnsToDrop, function($column) {
                return Schema::hasColumn('tasks', $column);
            });

            if (!empty($existingColumns)) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};

