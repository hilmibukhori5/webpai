<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\ModuleCourse;
use App\Models\PaiModule;
use Database\Seeders\Data\ModuleCourseMap;
use Illuminate\Database\Seeder;

class ModuleCourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Truncate dulu supaya entry yang dihapus dari map ikut terhapus dari DB.
        ModuleCourse::truncate();

        foreach (ModuleCourseMap::all() as $moduleCode => $module) {
            $paiModule = PaiModule::where('code', $moduleCode)->firstOrFail();

            foreach (['baru', 'lama'] as $curriculum) {
                foreach ($module[$curriculum] as $courseData) {
                    $course = Course::where('code', $courseData['code'])->firstOrFail();

                    ModuleCourse::create([
                        'pai_module_id' => $paiModule->id,
                        'course_id' => $course->id,
                        'curriculum' => $curriculum,
                        'prodi' => 'S1 Ilmu Aktuaria',
                    ]);
                }
            }

            // Entry eksklusif S1 Matematika untuk modul yang punya kursus Matematika.
            foreach ($module['matematika'] ?? [] as $courseData) {
                $course = Course::where('code', $courseData['code'])->firstOrFail();

                ModuleCourse::create([
                    'pai_module_id' => $paiModule->id,
                    'course_id' => $course->id,
                    'curriculum' => 'baru',
                    'prodi' => 'S1 Matematika',
                ]);
            }
        }
    }
}
