<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed the application's database with comprehensive demo data.
     *
     * @return void
     */
    public function run()
    {
        // Nonaktifkan pengecekan foreign key untuk menghindari error urutan
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Kosongkan tabel untuk memastikan data bersih
        DB::table('users')->truncate();
        DB::table('leads')->truncate();
        DB::table('subscriptions')->truncate();
        DB::table('tasks')->truncate();
        DB::table('campaigns')->truncate();
        DB::table('wa_templates')->truncate();

        // Panggil seeder individual
        $this->call([
            UserSeeder::class,
            LeadSeeder::class,
            SubscriptionSeeder::class,
            TaskSeeder::class,
            CampaignSeeder::class,
            WATemplateSeeder::class,
        ]);

        // Aktifkan kembali pengecekan foreign key
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Demo data has been seeded successfully!');
    }
}
