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
        Schema::table('leads', function (Blueprint $table) {
            // Mengganti nama kolom 'user_id' menjadi 'owner_id'
            $table->renameColumn('user_id', 'owner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Mengembalikan nama kolom 'owner_id' menjadi 'user_id'
            $table->renameColumn('owner_id', 'user_id');
        });
    }
};
