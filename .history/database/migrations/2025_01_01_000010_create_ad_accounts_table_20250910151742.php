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
        // This table stores credentials/identifiers for external ad platforms.
        Schema::create('ad_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('platform', ['google_ads', 'facebook_ads', 'tiktok_ads']);
            $table->string('account_id')->unique(); // The unique ID from the ad platform
            $table->text('api_token')->nullable(); // Encrypted API token/key
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_accounts');
    }
};
