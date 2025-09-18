<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = 'lead_follow_up_rules';

        // 1) Jika tabel BELUM ada → buat lengkap (tanpa FK ke w_a_templates)
        if (!Schema::hasTable($table)) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();

                // Null = global rule, non-null = khusus lead tertentu
                $t->unsignedBigInteger('lead_id')->nullable()->index();

                // Kondisi standar agar tidak ubah arsitektur
                $t->string('condition', 50)->index();

                // Kirim setelah X hari sejak anchor waktu sesuai kondisi
                $t->unsignedSmallInteger('days_after')->default(3);

                // Opsional: pakai template & sender tertentu (tanpa FK paksa ke template)
                $t->unsignedBigInteger('wa_template_id')->nullable()->index();
                $t->unsignedBigInteger('waha_sender_id')->nullable()->index();

                // Aktif/nonaktifkan rule
                $t->boolean('is_active')->default(true)->index();

                // Metadata
                $t->unsignedBigInteger('created_by')->nullable()->index();
                $t->unsignedBigInteger('updated_by')->nullable()->index();

                // Catatan terakhir eksekusi rule
                $t->timestamp('last_run_at')->nullable()->index();

                $t->timestamps();
            });

            // Tambahkan FK yang PASTI ada saja (aman)
            Schema::table($table, function (Blueprint $t) {
                if (Schema::hasTable('leads') && Schema::hasColumn($t->getTable(), 'lead_id')) {
                    $t->foreign('lead_id')->references('id')->on('leads')->nullOnDelete();
                }
                if (Schema::hasTable('waha_senders') && Schema::hasColumn($t->getTable(), 'waha_sender_id')) {
                    $t->foreign('waha_sender_id')->references('id')->on('waha_senders')->nullOnDelete();
                }
                if (Schema::hasTable('users') && Schema::hasColumn($t->getTable(), 'created_by')) {
                    $t->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                }
                if (Schema::hasTable('users') && Schema::hasColumn($t->getTable(), 'updated_by')) {
                    $t->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
                }
            });

            return;
        }

        // 2) Jika tabel SUDAH ada → hanya tambah kolom yang belum ada (idempotent)
        Schema::table($table, function (Blueprint $t) use ($table) {
            if (!Schema::hasColumn($table, 'lead_id')) {
                $t->unsignedBigInteger('lead_id')->nullable()->index()->after('id');
            }
            if (!Schema::hasColumn($table, 'condition')) {
                $t->string('condition', 50)->index()->after('lead_id');
            }
            if (!Schema::hasColumn($table, 'days_after')) {
                $t->unsignedSmallInteger('days_after')->default(3)->after('condition');
            }
            if (!Schema::hasColumn($table, 'wa_template_id')) {
                $t->unsignedBigInteger('wa_template_id')->nullable()->index()->after('days_after');
            }
            if (!Schema::hasColumn($table, 'waha_sender_id')) {
                $t->unsignedBigInteger('waha_sender_id')->nullable()->index()->after('wa_template_id');
            }
            if (!Schema::hasColumn($table, 'is_active')) {
                $t->boolean('is_active')->default(true)->index()->after('waha_sender_id');
            }
            if (!Schema::hasColumn($table, 'created_by')) {
                $t->unsignedBigInteger('created_by')->nullable()->index()->after('is_active');
            }
            if (!Schema::hasColumn($table, 'updated_by')) {
                $t->unsignedBigInteger('updated_by')->nullable()->index()->after('created_by');
            }
            if (!Schema::hasColumn($table, 'last_run_at')) {
                $t->timestamp('last_run_at')->nullable()->index()->after('updated_by');
            }
            if (!Schema::hasColumn($table, 'created_at')) {
                $t->timestamps(); // menambah created_at & updated_at jika belum ada
            }
        });

        // Tambahkan FK yang aman saja; lewati bila sudah ada/inkompatibel
        // (Kita tidak memaksa, demi stabilitas)
        try {
            Schema::table($table, function (Blueprint $t) {
                if (Schema::hasTable('leads') && Schema::hasColumn($t->getTable(), 'lead_id')) {
                    // FK mungkin sudah ada; jika sudah, DB akan menolak—jadi biarkan try-catch
                    $t->foreign('lead_id')->references('id')->on('leads')->nullOnDelete();
                }
                if (Schema::hasTable('waha_senders') && Schema::hasColumn($t->getTable(), 'waha_sender_id')) {
                    $t->foreign('waha_sender_id')->references('id')->on('waha_senders')->nullOnDelete();
                }
                if (Schema::hasTable('users') && Schema::hasColumn($t->getTable(), 'created_by')) {
                    $t->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                }
                if (Schema::hasTable('users') && Schema::hasColumn($t->getTable(), 'updated_by')) {
                    $t->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
                }
            });
        } catch (\Throwable $e) {
            // Diamkan: tujuan kita menjaga migrasi tetap idempotent & tidak crash
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_follow_up_rules');
    }
};
