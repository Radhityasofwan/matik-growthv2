// database/migrations/xxxx_xx_xx_xxxxxx_create_subscriptions_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->onDelete('cascade');
            $table->string('plan'); // e.g., Basic, Premium
            $table->decimal('amount', 15, 2);
            $table->string('cycle'); // e.g., monthly, yearly
            $table->string('status')->default('active'); // active, paused, cancelled
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
