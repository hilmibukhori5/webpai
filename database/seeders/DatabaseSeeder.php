<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Test User dijadikan admin (bukan student) -- user student wajib
        // punya profil Student (no_induk/prodi), demo data lengkap itu Fase 7.
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => UserRole::Admin,
        ]);

        $this->call([
            PaiModuleSeeder::class,
            CourseSeeder::class,
            ModuleCourseSeeder::class,
        ]);
    }
}
