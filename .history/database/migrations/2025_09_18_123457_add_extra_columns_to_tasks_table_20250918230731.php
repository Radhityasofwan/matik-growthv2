<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false)->after('creator_id');
            }
            if (!Schema::hasColumn('tasks', 'progress')) {
                $table->tinyInteger('progress')->unsigned()->default(0)->after('is_pinned');
            }
            if (!Schema::hasColumn('tasks', 'icon')) {
                $table->string('icon')->nullable()->after('progress');
            }
            if (!Schema::hasColumn('tasks', 'start_at')) {
                $table->timestamp('start_at')->nullable()->after('due_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $cols = ['is_pinned', 'start_at', 'progress', 'icon'];
            $drop = array_values(array_filter($cols, fn($c) => Schema::hasColumn('tasks', $c)));
            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
