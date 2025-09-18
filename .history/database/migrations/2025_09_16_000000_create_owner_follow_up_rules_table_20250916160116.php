<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends \Illuminate\Database\Migrations\Migration {
    public function up(): void {
        Schema::create('owner_follow_up_rules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('lead_id')->nullable()->constrained()->nullOnDelete(); // scope spesifik lead (opsional)
            $t->enum('condition', ['no_chat','chat_1_no_reply','chat_2_no_reply','chat_3_no_reply']);
            $t->unsignedInteger('days_after')->default(0);
            $t->foreignId('template_id')->nullable()->constrained('wa_templates')->nullOnDelete();
            $t->foreignId('sender_id')->nullable()->constrained('waha_senders')->nullOnDelete();
            $t->boolean('is_active')->default(true);
            $t->timestamp('last_run_at')->nullable();
            $t->timestamps();

            $t->unique(['lead_id','condition','days_after'], 'uniq_owner_rule_scope');
        });
    }
    public function down(): void { Schema::dropIfExists('owner_follow_up_rules'); }
};
