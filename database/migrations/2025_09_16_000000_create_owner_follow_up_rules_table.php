<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('owner_follow_up_rules', function (Blueprint $table) {
            $table->id();

            // Scope: null = global, isi = untuk lead tertentu
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();

            // Trigger:
            // - on_send|on_success|on_fail   => dipicu proses follow-up lead
            // - on_trial_ends_at|on_due_at   => dipicu sweep tanggal
            $table->string('trigger', 40);

            // H-1, H-3, dst (wajib untuk trigger bertipe tanggal; null untuk on_send/on_success/on_fail)
            $table->unsignedInteger('days_before')->nullable();

            // Template (opsional). Jika null -> fallback teks default
            $table->foreignId('template_id')->nullable()->constrained('wa_templates')->nullOnDelete();

            // Sender (opsional). Jika null -> fallback: sender rule lead atau default aktif
            $table->foreignId('sender_id')->nullable()->constrained('waha_senders')->nullOnDelete();

            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();

            $table->timestamps();

            // Index yang sering dipakai
            $table->index(['trigger', 'is_active']);
            $table->index(['lead_id', 'trigger']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_follow_up_rules');
    }
};
