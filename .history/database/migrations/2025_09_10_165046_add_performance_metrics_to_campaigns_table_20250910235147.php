<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->decimal('total_spent', 15, 2)->default(0)->after('revenue');
            $table->unsignedBigInteger('impressions')->default(0)->after('total_spent');
            $table->unsignedBigInteger('link_clicks')->default(0)->after('impressions');
            $table->unsignedBigInteger('results')->default(0)->after('link_clicks'); // Hasil/Konversi
            $table->unsignedBigInteger('lp_impressions')->default(0)->after('results');
            $table->unsignedBigInteger('lp_link_clicks')->default(0)->after('lp_impressions');
        });
    }
    public function down(): void {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['total_spent', 'impressions', 'link_clicks', 'results', 'lp_impressions', 'lp_link_clicks']);
        });
    }
};