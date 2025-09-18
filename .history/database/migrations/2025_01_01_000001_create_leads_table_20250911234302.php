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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            // Identitas & kontak
            $table->string('name');                   // Nama Lead (tetap disimpan)
            $table->string('email')->unique();
            $table->string('phone', 30)->nullable();  // No. Whatsapp
            $table->string('store_name')->nullable(); // Nama Toko

            // Status
            $table->enum('status', ['trial', 'active', 'converted', 'churn'])->default('trial')->index();
            $table->integer('score')->default(0);

            // Relasi owner (PIC)
            $table->foreignId('owner_id')->nullable()
                  ->constrained('users')->nullOnDelete(); // onDelete('set null')

            // Tanggal daftar & tanggal habis (trial)
            $table->timestamp('trial_ends_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
