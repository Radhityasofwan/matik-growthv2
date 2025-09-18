<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // ubah enum ke string agar fleksibel
            $table->string('status', 50)->default('open')->change();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // fallback ke enum lama (kalau memang awalnya enum)
            $table->enum('status', ['open','in_progress','done'])
                  ->default('open')
                  ->change();
        });
    }
};
