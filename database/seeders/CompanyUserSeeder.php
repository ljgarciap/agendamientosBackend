<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;

class CompanyUserSeeder extends Seeder
{
    public function run(): void
    {
        $barberia = Company::where('name', 'Barbería Estilo')->first();
        $dental = Company::where('name', 'Consultorio Dental')->first();
        $spa = Company::where('name', 'Spa Relax')->first();

        // 1. ADMINS
        // Shared Admin for Barbería and Dental
        if ($barberia) {
            $adminMulti = User::firstOrCreate(
                ['email' => 'multi_admin@softclass.com', 'company_id' => $barberia->id],
                ['name' => 'Admin MultiNegocios', 'password' => bcrypt('password')]
            );
            $adminMulti->assignRole('admin');
        }
        if ($dental) {
            $adminMulti2 = User::firstOrCreate(
                ['email' => 'multi_admin@softclass.com', 'company_id' => $dental->id],
                ['name' => 'Admin MultiNegocios', 'password' => bcrypt('password')]
            );
            $adminMulti2->assignRole('admin');
        }

        // Distinct Admin for Spa
        if ($spa) {
            $adminSpa = User::firstOrCreate(
                ['email' => 'spa_admin@softclass.com', 'company_id' => $spa->id],
                ['name' => 'Gerente Spa', 'password' => bcrypt('password')]
            );
            $adminSpa->assignRole('admin');
        }


        // 2. EMPLOYEES (Providers) - One per company
        if ($barberia) {
            $emp = User::firstOrCreate(
                ['email' => 'barbero@barberia.com', 'company_id' => $barberia->id],
                ['name' => 'Barbero Profesional', 'password' => bcrypt('password')]
            );
            $emp->assignRole('user');
        }

        if ($dental) {
            $emp = User::firstOrCreate(
                ['email' => 'dentista@dental.com', 'company_id' => $dental->id],
                ['name' => 'Dentista Experto', 'password' => bcrypt('password')]
            );
            $emp->assignRole('user');
        }

        if ($spa) {
            $emp = User::firstOrCreate(
                ['email' => 'masajista@spa.com', 'company_id' => $spa->id],
                ['name' => 'Terapista Zen', 'password' => bcrypt('password')]
            );
            $emp->assignRole('user');
        }


        // 3. CLIENTS
        // VIP Client: Access to ALL 3 companies
        $vipEmail = 'vip_client@gmail.com';
        foreach ([$barberia, $dental, $spa] as $company) {
            if ($company) {
                $vip = User::firstOrCreate(
                    ['email' => $vipEmail, 'company_id' => $company->id],
                    ['name' => 'Cliente VIP', 'password' => bcrypt('password')]
                );
                $vip->assignRole('client');
            }
        }

        // Regular Client: Access only to Barbería
        if ($barberia) {
            $reg = User::firstOrCreate(
                ['email' => 'regular_client@gmail.com', 'company_id' => $barberia->id],
                ['name' => 'Cliente Regular', 'password' => bcrypt('password')]
            );
            $reg->assignRole('client');
        }
    }
}
