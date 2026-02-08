<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::create([
            'name' => 'Barbería Estilo',
            'logo_url' => 'https://cdn-icons-png.flaticon.com/512/3655/3655610.png',
            'primary_color_hex' => '0xFFD32F2F', // Red
            'about_us' => 'Los mejores cortes y estilos para caballeros.',
        ]);

        Company::create([
            'name' => 'Consultorio Dental',
            'logo_url' => 'https://cdn-icons-png.flaticon.com/512/2966/2966334.png',
            'primary_color_hex' => '0xFF0288D1', // Light Blue
            'about_us' => 'Cuidamos tu sonrisa con la mejor tecnología.',
        ]);

        Company::create([
            'name' => 'Spa Relax',
            'logo_url' => 'https://cdn-icons-png.flaticon.com/512/2913/2913584.png',
            'primary_color_hex' => '0xFF4CAF50', // Green
            'about_us' => 'Relájate y renueva tu energía.',
        ]);
    }
}
