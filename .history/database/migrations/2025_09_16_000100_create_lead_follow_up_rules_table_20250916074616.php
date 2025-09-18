<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_follow_up_rules', function (Blueprint $table) {
            $table->id();

            // Null = global rule, non-null = khusus lead tertentu
            $table->unsignedBigInteger('lead_id')->nullable()->index();

            // Kondisi standar agar tidak ubah arsitektur
            $table->string('condition', 50)->index();

            // Kirim setelah X hari sejak anchor waktu sesuai kondisi
            $table->unsignedSmallInteger('days_after')->default(3);

            // Opsional: kirim pakai template dan/atau sender tertentu
            $table->unsignedBigInteger('wa_template_id')->nullable()->index();
            $table->unsignedBigInteger('waha_sender_id')->nullable()->index();

            // Aktif/nonaktifkan rule
            $table->boolean('is_active')->default(true)->index();

            // Metadata
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();

            // Catatan terakhir eksekusi rule
            $table->timestamp('last_run_at')->nullable()->index();

            $table->timestamps();

            // Hanya foreign key yang pasti ada
            $table->foreign('lead_id')->references('id')->on('leads')->nullOnDelete();
            $table->foreign('waha_sender_id')->references('id')->on('waha_senders')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            // ⚠️ Tidak bikin FK ke wa_template_id → supaya migrasi aman
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_follow_up_rules');
    }
};
