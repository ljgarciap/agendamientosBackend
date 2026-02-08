<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            ['key' => 'app_name', 'value' => 'Agendamientos'],
            ['key' => 'company_name', 'value' => 'SoftClass Inc.'],
            ['key' => 'primary_color_hex', 'value' => '0xFF673AB7'], // DeepPurple format for Flutter
            ['key' => 'logo_url', 'value' => 'https://via.placeholder.com/150'], // Default placeholder
            ['key' => 'about_us', 'value' => 'Somos una empresa dedicada a gestionar tus citas de manera eficiente y rápida. Confía en nosotros para organizar tu tiempo.'],
        ];

        foreach ($configs as $config) {
            DB::table('configurations')->updateOrInsert(
                ['key' => $config['key']],
                ['value' => $config['value'], 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
