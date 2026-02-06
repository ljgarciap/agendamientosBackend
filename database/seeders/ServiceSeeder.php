<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'name' => 'Mantenimiento General',
                'detail' => 'Revisión y reparaciones locativas generales.',
                'category' => 'Hogar',
                'icon' => 'home_repair_service', // Material Icon name
            ],
            [
                'name' => 'Plomería',
                'detail' => 'Reparación de fugas, instalación de grifos y baños.',
                'category' => 'Hogar',
                'icon' => 'plumbing',
            ],
            [
                'name' => 'Electricidad',
                'detail' => 'Instalación de luminarias, tomas y revisión de cableado.',
                'category' => 'Hogar',
                'icon' => 'electrical_services',
            ],
            [
                'name' => 'Jardinería',
                'detail' => 'Poda de césped y mantenimiento de zonas verdes.',
                'category' => 'Exteriores',
                'icon' => 'yard',
            ],
            [
                'name' => 'Limpieza',
                'detail' => 'Aseo profundo de casas y oficinas.',
                'category' => 'Hogar',
                'icon' => 'cleaning_services',
            ],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}
