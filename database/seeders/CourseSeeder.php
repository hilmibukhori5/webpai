<?php

namespace Database\Seeders;

use App\Models\Course;
use Database\Seeders\Data\ModuleCourseMap;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (ModuleCourseMap::all() as $module) {
            foreach (['baru', 'lama'] as $curriculum) {
                foreach ($module[$curriculum] as $course) {
                    // Beberapa code dipakai di kedua kurikulum (lihat catatan
                    // bagian 2 spec) — updateOrCreate by code supaya tidak dobel.
                    Course::updateOrCreate(
                        ['code' => $course['code']],
                        ['name' => $course['name'], 'sks' => $course['sks']],
                    );
                }
            }
        }
    }
}
