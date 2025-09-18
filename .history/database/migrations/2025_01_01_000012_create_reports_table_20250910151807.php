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
        // This table stores metadata for generated reports.
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // User who generated the report
            $table->string('type'); // e.g., 'sales_funnel', 'campaign_roi'
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('file_path')->nullable(); // Path to the generated PDF/Excel file
            $table->json('metadata')->nullable(); // Store parameters used for the report
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
