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
        // This table logs daily performance metrics from ad accounts for specific campaigns.
        Schema::create('ad_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_account_id')->constrained()->onDelete('cascade');
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->decimal('spend', 10, 2)->default(0); // Amount spent in currency
            $table->unsignedInteger('conversions')->default(0);
            $table->timestamps();

            $table->unique(['ad_account_id', 'campaign_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_metrics');
    }
};
