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
        Schema::table('owner_follow_up_rules', function (Blueprint $table) {
            // Menambahkan kolom 'days_before' setelah kolom 'trigger'
            // Dibuat nullable dengan default 0 untuk keamanan
            $table->integer('days_before')->nullable()->default(0)->after('trigger');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('owner_follow_up_rules', function (Blueprint $table) {
            $table->dropColumn('days_before');
        });
    }
};
