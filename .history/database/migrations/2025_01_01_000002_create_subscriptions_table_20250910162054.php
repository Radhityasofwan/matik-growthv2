<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->onDelete('cascade');
            $table->string('plan'); // e.g., 'basic', 'premium'
            $table->string('status'); // e.g., 'active', 'cancelled', 'expired'
            $table->decimal('amount', 8, 2);
            $table->string('cycle'); // e.g., 'monthly', 'yearly'
            $table->boolean('auto_renew')->default(true);

            // --- FIX: Menambahkan kolom start_date dan end_date ---
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscriptions');
    }
};

