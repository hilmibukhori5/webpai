<?php

namespace Database\Seeders;

use App\Models\PaiModule;
use Database\Seeders\Data\ModuleCourseMap;
use Illuminate\Database\Seeder;

class PaiModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (ModuleCourseMap::all() as $code => $module) {
            PaiModule::updateOrCreate(
                ['code' => $code],
                ['name' => $module['name']],
            );
        }
    }
}
