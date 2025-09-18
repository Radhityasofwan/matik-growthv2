<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\WATemplate;

class WATemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        WATemplate::create([
            'name' => 'Welcome Trial',
            'body' => 'Hi {{name}}! Welcome to Matik Growth Hub. We are excited to have you on board. Let us know if you have any questions.',
            'variables' => ['{{name}}'],
        ]);

        WATemplate::create([
            'name' => 'Trial Expiring H-3',
            'body' => 'Hi {{name}}, your trial period is expiring in 3 days. Upgrade your plan to keep your access. Contact us for any help!',
            'variables' => ['{{name}}'],
        ]);
    }
}
