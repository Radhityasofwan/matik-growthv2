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
            // Menambahkan kolom 'trigger' setelah kolom 'lead_id'
            $table->string('trigger')->after('lead_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('owner_follow_up_rules', function (Blueprint $table) {
            $table->dropColumn('trigger');
        });
    }
};
